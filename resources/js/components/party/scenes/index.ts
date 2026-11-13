import type { Component } from 'vue';
import SceneBresil from './SceneBresil.vue';
import SceneBsr from './SceneBsr.vue';
import SceneCapCaval from './SceneCapCaval.vue';
import SceneChambre from './SceneChambre.vue';
import SceneFalafel from './SceneFalafel.vue';
import SceneMariage from './SceneMariage.vue';

/**
 * Décors des étapes, indexés par la clé `scene` définie côté PHP (VoieDeLaSagesse::steps()).
 */
export const scenes: Record<string, Component> = {
    chambre: SceneChambre,
    bsr: SceneBsr,
    'cap-caval': SceneCapCaval,
    bresil: SceneBresil,
    falafel: SceneFalafel,
    mariage: SceneMariage,
};
