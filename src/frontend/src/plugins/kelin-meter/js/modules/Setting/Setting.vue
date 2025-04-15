<template>
  <div>
    <widget color="green" title="Settings">
      <div class="md-layout md-gutter">
        <div
          class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-100"
        >
          <md-card class="setting-card">
            <md-card-header>Synchronization Settings</md-card-header>
            <md-card-content>
              <form data-vv-scope="Synchronization-Form">
                <div v-for="(setting, i) in settingService.list" :key="i">
                  <div class="md-layout md-gutter">
                    <div
                      class="md-layout-item md-xlarge-size-25 md-large-size-25 md-medium-size-25 md-small-size-25"
                    >
                      <md-field>
                        <label>
                          {{ setting.actionName }}
                        </label>
                      </md-field>
                    </div>
                    <div
                      class="md-layout-item md-xlarge-size-25 md-large-size-25 md-medium-size-25 md-small-size-25"
                    >
                      <md-field
                        :class="{
                          'md-invalid': errors.has(
                            'Synchronization-Form.each_' + setting.id,
                          ),
                        }"
                      >
                        <label for="per">Each</label>
                        <md-input
                          min="1"
                          :id="'each_' + setting.id"
                          :name="'each_' + setting.id"
                          v-model="setting.syncInValueNum"
                          type="number"
                          v-validate="'required|min_value:1'"
                        />
                        <span class="md-error">
                          {{
                            errors.first(
                              "Synchronization-Form.each_" + setting.id,
                            )
                          }}
                        </span>
                      </md-field>
                    </div>
                    <div
                      class="md-layout-item md-xlarge-size-25 md-large-size-25 md-medium-size-25 md-small-size-25"
                    >
                      <md-field>
                        <label for="period">
                          {{ $tc("words.period") }}
                        </label>
                        <md-select
                          name="period"
                          v-model="setting.syncInValueStr"
                          id="period"
                          v-validate="'required'"
                        >
                          <md-option
                            v-for="(p, i) in syncPeriods"
                            :value="p"
                            :key="i"
                          >
                            {{ p }}(s)
                          </md-option>
                        </md-select>
                      </md-field>
                    </div>
                    <div
                      class="md-layout-item md-xlarge-size-25 md-large-size-25 md-medium-size-25 md-small-size-25"
                    >
                      <md-field
                        :class="{
                          'md-invalid': errors.has(
                            'Synchronization-Form.max_attempt_' + setting.id,
                          ),
                        }"
                      >
                        <label for="max_attempt">Maximum Attempt(s)</label>
                        <md-input
                          :id="'max_attempt_' + setting.id"
                          :name="'max_attempt_' + setting.id"
                          v-model="setting.maxAttempts"
                          type="number"
                          min="1"
                          v-validate="'required|min_value:1'"
                        />
                        <span class="md-error">
                          {{
                            errors.first(
                              "Synchronization-Form.max_attempt_" + setting.id,
                            )
                          }}
                        </span>
                      </md-field>
                    </div>
                  </div>
                </div>
              </form>
            </md-card-content>
            <md-card-actions>
              <md-button
                class="md-raised md-primary"
                @click="updateSyncSetting()"
              >
                Save
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
      loadingSms: false,
      syncPeriods: ["year", "month", "hour", "week", "day", "minute"],
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
  d-webkit-border-radius: 16px;
  -moz-border-radius: 16px;
  border-radius: 16px;
}
</style>
