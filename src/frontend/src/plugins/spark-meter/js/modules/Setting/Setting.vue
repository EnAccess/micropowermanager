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
                  <div
                    v-if="setting.settingTypeName === 'spark_sync_setting'"
                    class="md-layout md-gutter"
                  >
                    <div
                      class="md-layout-item md-xlarge-size-25 md-large-size-25 md-medium-size-25 md-small-size-25"
                    >
                      <md-field>
                        <label>
                          {{ setting.settingType.actionName }}
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
                          v-model="setting.settingType.syncInValueNum"
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
                          v-model="setting.settingType.syncInValueStr"
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
                          v-model="setting.settingType.maxAttempts"
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
        <div
          class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-100"
        >
          <md-card class="setting-card">
            <md-card-header>Sms Settings</md-card-header>
            <md-card-content>
              <md-tabs>
                <md-tab
                  @click="tab = 'main-settings'"
                  id="tab-main-settings"
                  md-label="Main Settings"
                >
                  <form data-vv-scope="Main-Form">
                    <div v-for="(setting, i) in settingService.list" :key="i">
                      <div
                        v-if="setting.settingTypeName === 'spark_sms_setting'"
                        class="md-layout md-gutter"
                      >
                        <div
                          class="md-layout-item md-xlarge-size-33 md-large-size-33 md-medium-size-33 md-small-size-33"
                        >
                          <md-field>
                            <label>
                              {{ setting.settingType.state }}
                            </label>
                          </md-field>
                        </div>
                        <div
                          class="md-layout-item md-xlarge-size-33 md-large-size-33 md-medium-size-33 md-small-size-33"
                        >
                          <md-field
                            :class="{
                              'md-invalid': errors.has(
                                'Main-Form.send_elder_' + setting.id,
                              ),
                            }"
                          >
                            <label for="send_elder">
                              Consider Only (created in last X minutes)
                            </label>
                            <md-input
                              :id="'send_elder_' + setting.id"
                              :name="'send_elder_' + setting.id"
                              v-model="setting.settingType.NotSendElderThanMins"
                              type="number"
                              min="10"
                              v-validate="'required|min_value:10'"
                            />
                            <span class="md-error">
                              {{
                                errors.first(
                                  "Main-Formsend_elder_" + setting.id,
                                )
                              }}
                            </span>
                          </md-field>
                        </div>

                        <div
                          class="md-layout-item md-xlarge-size-33 md-large-size-33 md-medium-size-33 md-small-size-33"
                        >
                          <md-checkbox
                            v-model="setting.settingType.enabled"
                            v-validate="'required'"
                          >
                            Enabled
                          </md-checkbox>
                        </div>
                      </div>
                    </div>
                  </form>
                </md-tab>
                <md-tab
                  @click="tab = 'notification-settings'"
                  id="tab-notification-settings"
                  md-label="Notification Settings"
                >
                  <div
                    v-for="(
                      smsBody, index
                    ) in smsBodiesService.lowBalanceNotifierList"
                    :key="index"
                  >
                    <sms-body
                      ref="smsBody_notification_ref"
                      :sms-variable-default-values="
                        smsVariableDefaultValueService.list
                      "
                      :sms-body="smsBody"
                    />
                  </div>
                </md-tab>
                <md-tab
                  @click="tab = 'meter-reset-settings'"
                  id="tab-meter-reset-settings"
                  md-label="Meter Reset Feedback Settings"
                >
                  <div class="md-layout md-gutter md-size-100">
                    <div class="md-layout-item notice-message-area">
                      <p style="font-size: large; font-weight: 500">
                        {{ $tc("words.notice") }} !
                      </p>
                      Meter Reset Key is for the customers that want to clear
                      the meter's error state (throttle error or protect) if one
                      exists. When the customers want to reset their meter, they
                      need to send this key as SMS"
                    </div>
                    <div class="md-layout-item md-size-100">
                      <md-field
                        :class="{
                          'md-invalid': errors.has('meter_reset_key'),
                        }"
                      >
                        <label for="meter_reset_key">Meter Reset Key</label>
                        <md-input
                          v-model="feedbackWordService.feedbackWords.meterReset"
                          id="meter_reset_key"
                          name="meter_reset_key"
                          v-validate="'required'"
                        ></md-input>
                        <span class="md-error">
                          {{ errors.first("meter_reset_key") }}
                        </span>
                      </md-field>
                    </div>
                    <div class="md-layout-item md-size-100">
                      <div
                        v-for="(
                          smsBody, index
                        ) in smsBodiesService.meterResetFeedbackList"
                        :key="index"
                      >
                        <sms-body
                          ref="smsBody_meter_ref"
                          :sms-variable-default-values="
                            smsVariableDefaultValueService.list
                          "
                          :sms-body="smsBody"
                        />
                      </div>
                    </div>
                  </div>
                </md-tab>
                <md-tab
                  @click="tab = 'customer-balance-settings'"
                  id="tab-customer-balance-settings"
                  md-label="Customer Balance Feedback Settings"
                >
                  <div class="md-layout md-gutter md-size-100">
                    <div class="md-layout-item notice-message-area">
                      <p style="font-size: large; font-weight: 500">
                        {{ $tc("words.notice") }} !
                      </p>
                      Current Balance Key is for the customers that want to get
                      their current balance. When the customers want to get
                      their current balance, they need to send this key as SMS"
                    </div>
                    <div class="md-layout-item md-size-100">
                      <md-field
                        :class="{
                          'md-invalid': errors.has('meter_balance_key'),
                        }"
                      >
                        <label for="meter_balance_key">
                          Current Balance Key
                        </label>
                        <md-input
                          v-model="
                            feedbackWordService.feedbackWords.meterBalance
                          "
                          id="meter_balance_key"
                          name="meter_balance_key"
                          v-validate="'required'"
                        ></md-input>
                        <span class="md-error">
                          {{ errors.first("meter_balance_key") }}
                        </span>
                      </md-field>
                    </div>
                    <div class="md-layout-item md-size-100">
                      <div
                        v-for="(
                          smsBody, index
                        ) in smsBodiesService.balanceFeedbacksList"
                        :key="index"
                      >
                        <sms-body
                          ref="smsBody_balance_ref"
                          :sms-variable-default-values="
                            smsVariableDefaultValueService.list
                          "
                          :sms-body="smsBody"
                        />
                      </div>
                    </div>
                  </div>
                </md-tab>
              </md-tabs>
            </md-card-content>
            <md-card-actions>
              <md-button
                class="md-raised md-primary"
                @click="updateSmsSetting()"
              >
                Save
              </md-button>
            </md-card-actions>
            <md-progress-bar md-mode="indeterminate" v-if="loadingSms" />
          </md-card>
        </div>
      </div>
    </widget>
  </div>
</template>

<script>
import { SettingService } from "../../services/SettingService"
import Widget from "@/shared/Widget.vue"
import { SmsVariableDefaultValueService } from "../../services/SmsVariableDefaultValueService"
import { SmsBodiesService } from "../../services/SmsBodiesService"
import SmsBody from "./SmsBody"
import { SmFeedbackWordService } from "../../services/SmFeedbackWordService"
import { notify } from "@/mixins/notify"

export default {
  name: "Setting",
  mixins: [notify],
  components: { Widget, SmsBody },
  data() {
    return {
      settingService: new SettingService(),
      feedbackWordService: new SmFeedbackWordService(),
      loadingSync: false,
      loadingSms: false,
      syncPeriods: ["year", "month", "hour", "week", "day", "minute"],
      smsVariableDefaultValueService: new SmsVariableDefaultValueService(),
      smsBodiesService: new SmsBodiesService(),
      tab: "main-settings",
    }
  },
  created() {
    this.getSmsVariableDefaultValues()
  },
  mounted() {
    this.getSettings()
    this.getSmsBodies()
    this.getSmsFeedbackWords()
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
    async updateSmsSetting() {
      if (this.tab === "notification-settings") {
        let refs = this.$refs.smsBody_notification_ref
        await this.validateSmsBodies(refs)
        if (
          !this.smsBodiesService.lowBalanceNotifierList.filter(
            (x) => !x.validation,
          ).length
        ) {
          try {
            await this.smsBodiesService.updateSmsBodies(this.tab)
            this.alertNotify("success", "Updated Successfully")
          } catch (e) {
            this.alertNotify("error", e.message)
          }
        }
      } else if (this.tab === "meter-reset-settings") {
        let refs = this.$refs.smsBody_meter_ref
        await this.validateSmsBodies(refs)
        if (
          !this.smsBodiesService.meterResetFeedbackList.filter(
            (x) => !x.validation,
          ).length
        ) {
          try {
            await this.feedbackWordService.updateFeedbackWords()
            await this.smsBodiesService.updateSmsBodies(this.tab)
            this.alertNotify("success", "Updated Successfully")
          } catch (e) {
            this.alertNotify("error", e.message)
          }
        }
      } else if (this.tab === "customer-balance-settings") {
        let refs = this.$refs.smsBody_balance_ref
        await this.validateSmsBodies(refs)
        if (
          !this.smsBodiesService.balanceFeedbacksList.filter(
            (x) => !x.validation,
          ).length
        ) {
          try {
            await this.feedbackWordService.updateFeedbackWords()
            await this.smsBodiesService.updateSmsBodies(this.tab)
            this.alertNotify("success", "Updated Successfully")
          } catch (e) {
            this.alertNotify("error", e.message)
          }
        }
      } else {
        let validator = await this.$validator.validateAll("Main-Form")
        if (!validator) {
          return
        }
        try {
          this.loadingSms = true
          await this.settingService.updateSmsSettings()
          this.loadingSms = false
          this.alertNotify("success", "Sms settings updated.")
        } catch (e) {
          this.loadingSms = false
          this.alertNotify("error", e.message)
        }
      }
    },
    async getSmsVariableDefaultValues() {
      try {
        await this.smsVariableDefaultValueService.getSmsVariableDefaultValues()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async getSmsBodies() {
      try {
        await this.smsBodiesService.getSmsBodies()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async getSmsFeedbackWords() {
      try {
        await this.feedbackWordService.getFeedbackWords()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async validateSmsBodies(refs) {
      for (const ref of refs) {
        await ref.validateBody()
      }
    },
  },
}
</script>

<style scoped>
.notice-message-area {
  padding: 20px;
  background-color: #badee4;
  margin: 10px;
  d-webkit-border-radius: 16px;
  -moz-border-radius: 16px;
  border-radius: 16px;
}

.setting-card {
  padding: 2rem !important;
}
</style>
