package com.cocoon.plugins.localnotifications

import android.app.AlarmManager
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.os.Build
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeFunction
import com.nativephp.mobile.bridge.BridgeResponse
import org.json.JSONObject
import java.time.Instant

const val PREFS_NAME = "cocoon_local_notifications"
const val CHANNEL_ID = "cocoon_reminders"
const val CHANNEL_NAME = "Rappels Cocoon"

fun ensureNotificationChannel(context: Context) {
    if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
        val channel = NotificationChannel(
            CHANNEL_ID,
            CHANNEL_NAME,
            NotificationManager.IMPORTANCE_HIGH
        )
        val nm = context.getSystemService(NotificationManager::class.java)
        nm.createNotificationChannel(channel)
    }
}

/**
 * Bridge functions for local notifications
 * Namespace: "LocalNotification.*"
 */
object LocalNotificationFunctions {

    /**
     * Schedule a local notification
     * Parameters:
     *   - id: string — unique identifier
     *   - title: string — notification title
     *   - body: string (optional) — notification body
     *   - trigger_at: string — ISO 8601 datetime (e.g. "2026-03-05T09:00:00+01:00")
     */
    class Schedule(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val id = parameters["id"] as? String
                ?: return BridgeResponse.success(mapOf("scheduled" to false, "error" to "id required"))
            val title = parameters["title"] as? String
                ?: return BridgeResponse.success(mapOf("scheduled" to false, "error" to "title required"))
            val body = parameters["body"] as? String ?: ""
            val triggerAt = parameters["trigger_at"] as? String
                ?: return BridgeResponse.success(mapOf("scheduled" to false, "error" to "trigger_at required"))

            val triggerMillis = try {
                Instant.parse(triggerAt).toEpochMilli()
            } catch (e: Exception) {
                return BridgeResponse.success(mapOf("scheduled" to false, "error" to "invalid trigger_at"))
            }

            if (triggerMillis <= System.currentTimeMillis()) {
                return BridgeResponse.success(mapOf("scheduled" to false, "reason" to "past"))
            }

            ensureNotificationChannel(activity)

            val notifId = id.hashCode()
            val intent = Intent(activity, NotificationAlarmReceiver::class.java).apply {
                putExtra("notif_id", notifId)
                putExtra("notif_title", title)
                putExtra("notif_body", body)
            }
            val pendingIntent = PendingIntent.getBroadcast(
                activity, notifId, intent,
                PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
            )

            val alarmManager = activity.getSystemService(AlarmManager::class.java)
            alarmManager.setExactAndAllowWhileIdle(AlarmManager.RTC_WAKEUP, triggerMillis, pendingIntent)

            // Persist for boot recovery
            val prefs = activity.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
            val stored = JSONObject().apply {
                put("notif_id", notifId)
                put("title", title)
                put("body", body)
                put("trigger_millis", triggerMillis)
            }
            prefs.edit().putString("notif_$id", stored.toString()).apply()

            return BridgeResponse.success(mapOf("scheduled" to true, "id" to id))
        }
    }

    /**
     * Cancel a scheduled local notification by ID
     * Parameters:
     *   - id: string — the ID used when scheduling
     */
    class Cancel(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val id = parameters["id"] as? String
                ?: return BridgeResponse.success(mapOf("cancelled" to false, "error" to "id required"))

            val notifId = id.hashCode()
            val intent = Intent(activity, NotificationAlarmReceiver::class.java)
            val pendingIntent = PendingIntent.getBroadcast(
                activity, notifId, intent,
                PendingIntent.FLAG_NO_CREATE or PendingIntent.FLAG_IMMUTABLE
            )

            if (pendingIntent != null) {
                val alarmManager = activity.getSystemService(AlarmManager::class.java)
                alarmManager.cancel(pendingIntent)
                pendingIntent.cancel()
            }

            val prefs = activity.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
            prefs.edit().remove("notif_$id").apply()

            return BridgeResponse.success(mapOf("cancelled" to true, "id" to id))
        }
    }

    /**
     * Cancel all scheduled local notifications
     */
    class CancelAll(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val prefs = activity.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
            val all = prefs.all.filter { it.key.startsWith("notif_") }
            var count = 0

            for ((_, value) in all) {
                try {
                    val stored = JSONObject(value as String)
                    val notifId = stored.getInt("notif_id")
                    val intent = Intent(activity, NotificationAlarmReceiver::class.java)
                    val pi = PendingIntent.getBroadcast(
                        activity, notifId, intent,
                        PendingIntent.FLAG_NO_CREATE or PendingIntent.FLAG_IMMUTABLE
                    )
                    if (pi != null) {
                        val alarmManager = activity.getSystemService(AlarmManager::class.java)
                        alarmManager.cancel(pi)
                        pi.cancel()
                    }
                    count++
                } catch (e: Exception) {
                    // skip malformed entries
                }
            }

            prefs.edit().clear().apply()

            return BridgeResponse.success(mapOf("cancelled" to count))
        }
    }
}
