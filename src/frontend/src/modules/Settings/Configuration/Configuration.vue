<template>
  <div class="settings-area">
    <widget color="green" title="Settings">
      <md-tabs>
        <md-tab id="tab-home" md-icon="home" md-label="Main">
          <main-settings :mainSettings="mainSettings" />
        </md-tab>
        <md-tab id="tab-plugin" md-icon="widgets" md-label="Plugins">
          <plugin-settings :plugins="plugins" :mainSettings="mainSettings" />
        </md-tab>
        <md-tab id="tab-sms" name="sms" md-icon="sms" md-label="Sms">
          <sms-settings />
        </md-tab>

        <md-tab id="tab-map" md-icon="map" md-label="Map">
          <map-settings :mapSettings="mapSettings" />
        </md-tab>
      </md-tabs>
    </widget>
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import MainSettings from "./MainSettings"
import MapSettings from "./MapSettings"
import SmsSettings from "./SmsSettings"
import { MainSettingsService } from "@/services/MainSettingsService"
import { MapSettingsService } from "@/services/MapSettingsService"

import PluginSettings from "@/modules/Settings/Configuration/PluginSettings"
import { MpmPluginService } from "@/services/MpmPluginService"
import { PluginService } from "@/services/PluginService"
import { notify } from "@/mixins"

export default {
  name: "Settings",
  mixins: [notify],
  components: {
    PluginSettings,
    Widget,
    MainSettings,
    MapSettings,
    SmsSettings,
  },
  data() {
    return {
      mainSettingsService: new MainSettingsService(),
      mapSettingService: new MapSettingsService(),
      mpmPluginsService: new MpmPluginService(),
      pluginsService: new PluginService(),
      mapSettings: {},
      mainSettings: {},
      center: null,
      smsBodies: [],
      plugins: [],
    }
  },
  mounted() {
    this.getSettingStates()
    this.getPlugins()
  },
  methods: {
    async getSettingStates() {
      this.mainSettings = this.$store.getters["settings/getMainSettings"]
      this.mapSettings = this.$store.getters["settings/getMapSettings"]
    },
    async getPlugins() {
      const mpmPlugins = await this.mpmPluginsService.getMpmPlugins()
      const plugins = await this.pluginsService.getPlugins()

      this.plugins = mpmPlugins.map((plugin) => {
        let foundPlugin = plugins.find((p) => p.mpm_plugin_id === plugin.id)
        return {
          id: plugin.id,
          name: plugin.name,
          description: plugin.description,
          usage_type: plugin.usage_type,
          checked: foundPlugin && foundPlugin.status === 1,
        }
      })
    },
  },
}
</script>
<style scoped>
.settings-area {
  height: 100%;
  overflow: auto;
}
@media only screen and (max-width: 767px) {
  .settings-area {
    height: 200px;
  }
}
</style>
