<template>
  <div class="settings-area">
    <widget color="primary" title="Settings">
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
        <md-tab id="tab-api-keys" md-icon="vpn_key" md-label="API Keys">
          <api-keys-settings />
        </md-tab>
      </md-tabs>
    </widget>
  </div>
</template>

<script>
import ApiKeysSettings from "./ApiKeysSettings.vue"
import MainSettings from "./MainSettings.vue"
import MapSettings from "./MapSettings.vue"
import SmsSettings from "./SmsSettings.vue"

import { notify } from "@/mixins/notify.js"
import PluginSettings from "@/modules/Settings/Configuration/PluginSettings.vue"
import { MainSettingsService } from "@/services/MainSettingsService.js"
import { MapSettingsService } from "@/services/MapSettingsService.js"
import { MpmPluginService } from "@/services/MpmPluginService.js"
import { PluginService } from "@/services/PluginService.js"
import Widget from "@/shared/Widget.vue"

export default {
  name: "Settings",
  mixins: [notify],
  components: {
    PluginSettings,
    Widget,
    MainSettings,
    MapSettings,
    ApiKeysSettings,
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
<style scoped lang="scss">
.settings-area {
  height: 100%;
  overflow: auto;
}

@media only screen and (max-width: 767px) {
  .settings-area {
    height: auto;
  }

  ::v-deep .md-tabs-content {
    height: auto !important;
  }

  ::v-deep .md-tabs-navigation {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;

    &::-webkit-scrollbar {
      display: none;
    }
  }

  ::v-deep .md-tab-nav-button {
    min-width: auto;
    padding: 0 12px;
    flex-shrink: 0;
  }
}
</style>
