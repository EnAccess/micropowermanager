<template>
  <div>
    <md-tabs>
      <md-tab
        @click="tab = 'confirmation'"
        id="tab-confirmation"
        md-label="Transaction Confirmation"
      >
        <div
          v-for="(smsBody, index) in smsBodiesService.confirmationList"
          :key="index"
        >
          <sms-body
            ref="smsBody_confirmation_ref"
            :sms-variable-default-values="smsVariableDefaultValueService.list"
            :sms-body="smsBody"
          />
        </div>
      </md-tab>
      <md-tab @click="tab = 'reminder'" id="tab-reminder" md-label="Reminder">
        <sms-appliance-remind-rate />
        <div
          v-for="(smsBody, index) in smsBodiesService.reminderList"
          :key="index"
        >
          <sms-body
            ref="smsBody_reminder_ref"
            :sms-variable-default-values="smsVariableDefaultValueService.list"
            :sms-body="smsBody"
          />
        </div>
      </md-tab>
      <md-tab
        @click="tab = 'resend-information'"
        id="tab-resend-information"
        md-label="Resend Last Transaction"
      >
        <div class="md-layout md-gutter md-size-100">
          <div class="md-layout-item notice-message-area">
            <p style="font-size: large; font-weight: 500">
              {{ $tc("words.notice") }} !
            </p>
            {{ $tc("phrases.thisKeyWillUseIn") }}
          </div>
          <div class="md-layout-item md-size-100">
            <md-field
              :class="{
                'md-invalid': errors.has('resend_information_key'),
              }"
            >
              <label for="resend_information_key">
                Resend Last Transaction Information Key
              </label>
              <md-input
                v-model="
                  smsResendInformationKeyService.smsResendInformationKey.key
                "
                id="resend_information_key"
                name="resend_information_key"
                v-validate="'required'"
              ></md-input>
              <span class="md-error">
                {{ errors.first("resend_information_key") }}
              </span>
            </md-field>
          </div>
          <div class="md-layout-item md-size-100">
            <div
              v-for="(smsBody, index) in smsBodiesService.resendInformationList"
              :key="index"
            >
              <sms-body
                ref="smsBody_resend_ref"
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
        @click="tab = 'android-gateway'"
        id="tab-android-gateway"
        md-label="Android Gateway Settings"
      >
        <sms-android-setting
          :sms-android-settings="smsAndroidSettingsService.list"
        />
      </md-tab>
    </md-tabs>
    <div class="md-layout md-alignment-bottom-right">
      <md-button
        class="md-primary md-dense md-raised"
        @click="updateSmsBodies()"
        v-show="tab !== 'android-gateway'"
      >
        Save
      </md-button>
    </div>
    <md-progress-bar v-if="progress" md-mode="indeterminate"></md-progress-bar>
  </div>
</template>

<script>
import { SmsBodiesService } from "@/services/SmsBodiesService"
import SmsBody from "./SmsBody"
import { EventBus } from "@/shared/eventbus"
import { SmsResendInformationKeyService } from "@/services/SmsResendInformationKeyService"
import SmsApplianceRemindRate from "./SmsApplianceRemindRate"
import { SmsAndroidSettingService } from "@/services/SmsAndroidSettingService"
import SmsAndroidSetting from "./SmsAndroidSetting"
import { ErrorHandler } from "@/Helpers/ErrorHandler"
import { SmsVariableDefaultValueService } from "@/services/SmsVariableDefaultValueService"
import { notify } from "@/mixins/notify"

export default {
  name: "SmsSettings",
  mixins: [notify],
  components: { SmsAndroidSetting, SmsApplianceRemindRate, SmsBody },
  props: {
    smsBodies: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      tab: "confirmation",
      progress: false,
      smsBodiesService: new SmsBodiesService(),
      smsResendInformationKeyService: new SmsResendInformationKeyService(),
      smsAndroidSettingsService: new SmsAndroidSettingService(),
      smsVariableDefaultValueService: new SmsVariableDefaultValueService(),
      isValid: false,
    }
  },
  created() {
    this.getSmsVariableDefaultValues()
  },
  mounted() {
    this.getSmsBodies()
    this.getSmsResendInformationKey()
    this.getSmsAndroidSettings()
    EventBus.$on("smsAndroidSettingAdded", this.addAdditionalAndroidSetting)
    EventBus.$on(
      "smsAndroidSettingRemoved",
      this.removeAdditionalAndroidSetting,
    )
    EventBus.$on("smsAndroidSettingSaved", this.saveAdditionalAndroidSetting)
  },
  methods: {
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
    async getSmsAndroidSettings() {
      try {
        await this.smsAndroidSettingsService.getSmsAndroidSettings()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async getSmsResendInformationKey() {
      try {
        await this.smsResendInformationKeyService.getResendInformationKeys()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async updateSmsBodies() {
      if (this.tab === "confirmation") {
        let refs = this.$refs.smsBody_confirmation_ref
        await this.validateSmsBodies(refs)
        if (
          !this.smsBodiesService.confirmationList.filter((x) => !x.validation)
            .length
        ) {
          try {
            await this.smsBodiesService.updateSmsBodies(this.tab)
            this.alertNotify("success", "Updated Successfully")
            EventBus.$emit("Settings")
          } catch (e) {
            this.alertNotify("error", e.message)
          }
        }
      } else if (this.tab === "reminder") {
        let refs = this.$refs.smsBody_reminder_ref
        await this.validateSmsBodies(refs)
        if (
          !this.smsBodiesService.reminderList.filter((x) => !x.validation)
            .length
        ) {
          try {
            await this.smsBodiesService.updateSmsBodies(this.tab)
            this.alertNotify("success", "Updated Successfully")
            EventBus.$emit("Settings")
          } catch (e) {
            this.alertNotify("error", e.message)
          }
        }
      } else {
        let refs = this.$refs.smsBody_resend_ref
        await this.validateSmsBodies(refs)
        if (
          !this.smsBodiesService.resendInformationList.filter(
            (x) => !x.validation,
          ).length
        ) {
          try {
            await this.smsResendInformationKeyService.updateResendInformationKey()
            await this.smsBodiesService.updateSmsBodies(this.tab)
            this.alertNotify("success", "Updated Successfully")
            EventBus.$emit("Settings")
          } catch (e) {
            this.alertNotify("error", e.message)
          }
        }
      }
    },
    addAdditionalAndroidSetting() {
      this.smsAndroidSettingsService.addAdditionalSmsAndroidSettings()
    },
    async removeAdditionalAndroidSetting(smsAndroidSettingId) {
      try {
        await this.smsAndroidSettingsService.removeSmsAndroidSetting(
          smsAndroidSettingId,
        )
        this.alertNotify("success", "deleted successfully")
      } catch (e) {
        let errorMessage = e.response.data.message
        return new ErrorHandler(errorMessage, "http")
      }
    },
    async saveAdditionalAndroidSetting(smsAndroidSetting) {
      try {
        await this.smsAndroidSettingsService.saveSmsAndroidSetting(
          smsAndroidSetting,
        )
        if (smsAndroidSetting.id < 0) {
          this.alertNotify("success", "created successfully")
          return
        }
        this.alertNotify("success", "updated successfully")
        EventBus.$emit("Settings")
      } catch (e) {
        let errorMessage = e.response.data.message
        return new ErrorHandler(errorMessage, "http")
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
</style>
