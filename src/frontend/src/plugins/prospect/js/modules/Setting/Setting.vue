<template>
  <div>
    <widget color="green" title="Prospect Settings">
      <div class="md-layout md-gutter">
        <div
          class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-100"
        >
          <md-card class="setting-card">
            <md-card-header>Synchronization Settings</md-card-header>
            <md-card-content>
              <form data-vv-scope="Synchronization-Form">
                <div class="md-layout md-gutter">
                  <div
                    class="md-layout-item md-xlarge-size-25 md-large-size-25 md-medium-size-25 md-small-size-100"
                  >
                    <md-field>
                      <label>
                        {{ currentSetting.actionName }}
                      </label>
                    </md-field>
                  </div>
                  <div
                    class="md-layout-item md-xlarge-size-25 md-large-size-25 md-medium-size-25 md-small-size-100"
                  >
                    <md-field
                      :class="{
                        'md-invalid': errors.has('Synchronization-Form.each'),
                      }"
                    >
                      <label for="per">Each</label>
                      <md-input
                        id="each"
                        name="each"
                        v-model="currentSetting.syncInValueNum"
                        type="number"
                        min="1"
                        v-validate="'required|min_value:1'"
                      />
                      <span class="md-error">
                        {{ errors.first('Synchronization-Form.each') }}
                      </span>
                    </md-field>
                  </div>
                  <div
                    class="md-layout-item md-xlarge-size-25 md-large-size-25 md-medium-size-25 md-small-size-100"
                  >
                    <md-field
                      :class="{
                        'md-invalid': errors.has('Synchronization-Form.period'),
                      }"
                    >
                      <label for="period">
                        {{ $tc("words.period") }}
                      </label>
                      <md-select
                        name="period"
                        v-model="currentSetting.syncInValueStr"
                        id="period"
                        v-validate="'required'"
                      >
                        <md-option
                          v-for="(p, i) in syncPeriods"
                          :value="p.value"
                          :key="i"
                        >
                          {{ p.label }}
                        </md-option>
                      </md-select>
                      <span class="md-error">
                        {{ errors.first("Synchronization-Form.period") }}
                      </span>
                    </md-field>
                  </div>
                  <div
                    class="md-layout-item md-xlarge-size-25 md-large-size-25 md-medium-size-25 md-small-size-100"
                  >
                    <md-field
                      :class="{
                        'md-invalid': errors.has('Synchronization-Form.max_attempt'),
                      }"
                    >
                      <label for="max_attempt">Maximum Attempt(s)</label>
                      <md-input
                        id="max_attempt"
                        name="max_attempt"
                        v-model="currentSetting.maxAttempts"
                        type="number"
                        min="1"
                        v-validate="'required|min_value:1'"
                      />
                      <span class="md-error">
                        {{ errors.first("Synchronization-Form.max_attempt") }}
                      </span>
                    </md-field>
                  </div>
                </div>
              </form>
            </md-card-content>
            <md-card-actions>
              <md-button
                class="md-raised md-primary"
                @click="updateSyncSetting()"
              >
                Save Sync Settings
              </md-button>
            </md-card-actions>
            <md-progress-bar md-mode="indeterminate" v-if="loadingSync" />
          </md-card>
        </div>
      </div>
    </widget>
  </div>
</template>

<script>
import { SettingService } from "../../services/SettingService"
import Widget from "@/shared/Widget.vue"
import { notify } from "@/mixins/notify"

export default {
  name: "Setting",
  mixins: [notify],
  components: { Widget },
  data() {
    return {
      settingService: new SettingService(),
      loadingSync: false,
      syncPeriods: [
        { value: "everyMinute", label: "Every Minute" },
        { value: "everyFifteenMinutes", label: "Every 15 Minutes" },
        { value: "everyHour", label: "Hourly" },
        { value: "daily", label: "Daily" },
        { value: "weekly", label: "Weekly" },
        { value: "monthly", label: "Monthly" },
      ],
      currentSetting: {
        id: 1,
        actionName: "Installations",
        syncInValueNum: 1,
        syncInValueStr: "weekly",
        maxAttempts: 3,
      },
    }
  },
  mounted() {
    this.getSettings()
  },
  methods: {
    async getSettings() {
      await this.settingService.getSettings()
    },
    async updateSyncSetting() {
      let validator = await this.$validator.validateAll("Synchronization-Form")
      if (!validator) {
        return
      }
      try {
        this.loadingSync = true
        
        // Update the setting in the service
        const settingIndex = this.settingService.syncList.findIndex(s => s.id === this.currentSetting.id)
        if (settingIndex !== -1) {
          this.settingService.syncList[settingIndex] = { ...this.currentSetting }
        } else {
          this.settingService.syncList.push({ ...this.currentSetting })
        }
        
        await this.settingService.updateSyncSettings()
        this.loadingSync = false
        this.alertNotify("success", "Sync settings updated.")
      } catch (e) {
        this.loadingSync = false
        this.alertNotify("error", e.message)
      }
    },
  },
}
</script>

<style scoped>
.setting-card {
  padding: 2rem !important;
}

.notice-message-area {
  padding: 20px;
  background-color: #badee4;
  margin: 10px;
  -webkit-border-radius: 16px;
  -moz-border-radius: 16px;
  border-radius: 16px;
}
</style>
