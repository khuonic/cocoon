package com.cocoon.plugins.localnotifications

import android.app.AlarmManager
import android.app.PendingIntent
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import org.json.JSONObject

class NotificationAlarmReceiver : BroadcastReceiver() {

    override fun onReceive(context: Context, intent: Intent) {
        when (intent.action) {
            Intent.ACTION_BOOT_COMPLETED,
            Intent.ACTION_MY_PACKAGE_REPLACED -> rescheduleAll(context)
            else -> showNotification(context, intent)
        }
    }

    private fun showNotification(context: Context, intent: Intent) {
        val notifId = intent.getIntExtra("notif_id", 0)
        val title = intent.getStringExtra("notif_title") ?: return
        val body = intent.getStringExtra("notif_body") ?: ""

        ensureNotificationChannel(context)

        val notification = NotificationCompat.Builder(context, CHANNEL_ID)
            .setSmallIcon(android.R.drawable.ic_popup_reminder)
            .setContentTitle(title)
            .setContentText(body)
            .setPriority(NotificationCompat.PRIORITY_HIGH)
            .setAutoCancel(true)
            .build()

        NotificationManagerCompat.from(context).notify(notifId, notification)
    }

    private fun rescheduleAll(context: Context) {
        val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        val now = System.currentTimeMillis()
        val editor = prefs.edit()

        for ((key, value) in prefs.all) {
            if (!key.startsWith("notif_")) continue
            try {
                val stored = JSONObject(value as String)
                val triggerMillis = stored.getLong("trigger_millis")

                if (triggerMillis <= now) {
                    editor.remove(key)
                    continue
                }

                val notifId = stored.getInt("notif_id")
                val title = stored.getString("title")
                val body = stored.optString("body", "")

                val rescheduleIntent = Intent(context, NotificationAlarmReceiver::class.java).apply {
                    putExtra("notif_id", notifId)
                    putExtra("notif_title", title)
                    putExtra("notif_body", body)
                }
                val pi = PendingIntent.getBroadcast(
                    context, notifId, rescheduleIntent,
                    PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
                )

                val alarmManager = context.getSystemService(AlarmManager::class.java)
                alarmManager.setExactAndAllowWhileIdle(AlarmManager.RTC_WAKEUP, triggerMillis, pi)
            } catch (e: Exception) {
                editor.remove(key)
            }
        }

        editor.apply()
    }
}
