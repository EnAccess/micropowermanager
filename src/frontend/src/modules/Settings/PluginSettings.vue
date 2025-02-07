<template>
  <div>
    <div class="md-layout md-gutter">
      <div
        v-for="plugin in enrichedPlugins.filter(
          (p) => p.plugin_for_usage_type || p.checked,
        )"
        :key="plugin.id"
        class="box md-layout-item md-size-25 md-small-size-50"
      >
        <div class="header-text">{{ plugin.name }}</div>
        <div
          class="usage-type-warning"
          v-if="plugin.checked && !plugin.plugin_for_usage_type"
        >
          ⚠️ Plugin not supported for current usageType. It is recommended that
          you disable this plugin.
        </div>
        <small class="sub-text">{{ plugin.description }}</small>
        <div class="sub-text">Usage type: {{ plugin.usage_type }}</div>
        <md-switch
          v-model="plugin.checked"
          @change="onSwitchChange($event, plugin)"
          class="data-stream-switch"
          :disabled="switching"
        />
      </div>
    </div>
    <md-progress-bar md-mode="indeterminate" v-if="progressing" />
  </div>
</template>

<script>
import { MpmPluginService } from "@/services/MpmPluginService"
import { PluginService } from "@/services/PluginService"
import { notify } from "@/mixins/notify"

export default {
  name: "PluginSettings",
  mixins: [notify],
  data() {
    return {
      mpmPluginsService: new MpmPluginService(),
      pluginService: new PluginService(),
      progressing: false,
      switching: false,
    }
  },
  props: {
    plugins: {
      type: Array,
      required: true,
    },
    mainSettings: {
      type: Object,
      required: true,
    },
  },
  async created() {
    await this.$store.dispatch("settings/fetchPlugins")
  },
  computed: {
    enrichedPlugins: function () {
      return this.plugins.map((plugin) => ({
        ...plugin,
        plugin_for_usage_type: this.validUsageType(
          plugin.usage_type,
          this.mainSettings.usageType,
        ),
      }))
    },
  },
  methods: {
    async onSwitchChange(event, plugin) {
      this.switching = true
      this.progressing = true
      try {
        await this.pluginService.updatePlugin(plugin)
        await this.$store.dispatch("settings/fetchPlugins")
        this.alertNotify("success", "Plugin updated successfully")
      } catch (e) {
        this.switching = false
        this.alertNotify("error", e.message)
      }

      this.switching = false
      this.progressing = false
    },

    validUsageType(plugin_usage_type, customer_usage_types) {
      return (
        plugin_usage_type === "general" ||
        customer_usage_types.includes(plugin_usage_type)
      )
    },
  },
}
</script>

<style scoped lang="scss">
.box {
  border-radius: 5px;
  padding: 1.3vw;
  margin-top: 1vh;
  box-shadow:
    0 1px 5px -2px rgb(53 53 53 / 30%),
    0 0px 4px 0 rgb(0 0 0 / 12%),
    0 0px 0px -5px #8e8e8e;
}

.header-text {
  color: rgb(148, 148, 148);
  margin-top: 0px;
  margin-bottom: 1rem;
  font-size: 1.2rem;
  font-weight: bold;
}

.sub-text {
  font-weight: 400;
  font-size: 0.7rem;
}

.usage-type-warning {
  font-weight: 400;
  font-size: 0.7rem;
  background-color: #f0f0f0;
  padding: 5px;
  border-radius: 5px;
  color: #333333;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); // Optional: Add shadow for depth
  max-width: 400px; // Optional: Set maximum width for responsiveness
}

.stepper-title {
  text-align: center !important;
  font-size: large !important;
  padding: 1rem 1rem 0 1rem;
  margin-bottom: 3rem !important;
  font-weight: bolder !important;
}

.md-steppers-navigation {
  box-shadow: none;
  display: flex;
  border-bottom: 1px solid #bbb;
}

.data-stream-switch {
  margin-left: 3rem !important;
  float: right;
}
</style>
