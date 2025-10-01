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
                    class="md-layout-item md-xlarge-size-33 md-large-size-33 md-medium-size-33 md-small-size-100"
                  >
                    <md-field
                      :class="{
                        'md-invalid': errors.has('Synchronization-Form.actionType'),
                      }"
                    >
                      <label for="actionType">Action Type</label>
                      <md-select
                        name="actionType"
                        v-model="selectedActionType"
                        id="actionType"
                        v-validate="'required'"
                        @change="onActionTypeChange"
                      >
                        <md-option
                          v-for="(action, i) in actionTypes"
                          :value="action.value"
                          :key="i"
                        >
                          {{ action.label }}
                        </md-option>
                      </md-select>
                      <span class="md-error">
                        {{ errors.first("Synchronization-Form.actionType") }}
                      </span>
                    </md-field>
                  </div>
                  <div
                    class="md-layout-item md-xlarge-size-33 md-large-size-33 md-medium-size-33 md-small-size-100"
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
                    class="md-layout-item md-xlarge-size-33 md-large-size-33 md-medium-size-33 md-small-size-100"
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
      selectedActionType: "installations",
      actionTypes: [
        { value: "installations", label: "Installations" },
        { value: "payments", label: "Payments" },
      ],
      syncPeriods: [
        { value: "everyMinute", label: "Every Minute" },
        { value: "everyFifteenMinutes", label: "Every 15 Minutes" },
        { value: "weekly", label: "Weekly" },
        { value: "monthly", label: "Monthly" },
      ],
      currentSetting: {
        id: 1,
        actionName: "Installations",
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
    onActionTypeChange() {
      // Update the current setting based on selected action type
      this.currentSetting.actionName = this.selectedActionType.charAt(0).toUpperCase() + this.selectedActionType.slice(1)
      
      // Load settings for the selected action type
      const setting = this.settingService.syncList.find(s => s.actionName.toLowerCase() === this.selectedActionType)
      if (setting) {
        this.currentSetting = { ...setting }
      } else {
        // Default values for new action type
        this.currentSetting = {
          id: this.selectedActionType === "installations" ? 1 : 2,
          actionName: this.selectedActionType.charAt(0).toUpperCase() + this.selectedActionType.slice(1),
          syncInValueStr: "weekly",
          maxAttempts: 3,
        }
      }
    },
    async updateSyncSetting() {
      let validator = await this.$validator.validateAll("Synchronization-Form")
      if (!validator) {
        return
      }
      try {
        this.loadingSync = true
        
        // Update the setting in the service
        const settingIndex = this.settingService.syncList.findIndex(s => s.actionName.toLowerCase() === this.selectedActionType)
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
