<template>
  <div>
    <div
      class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100"
    >
      <md-button
        role="button"
        class="md-raised md-secondary"
        @click="addAdditionalSmsAndroidSetting()"
      >
        <md-icon>add</md-icon>
        {{ $tc("phrases.addAdditionalSmsAndroidSetting") }}
      </md-button>
    </div>
    <div
      class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100"
      v-for="(smsAndroidSetting, index) in smsAndroidSettings"
      :key="'smsAndroidSettings' + index"
    >
      <form class="md-layout md-gutter" data-vv-scope="SmsAndroidSettings-Form">
        <div
          class="md-layout-item md-xlarge-size-50 md-large-size-50 md-medium-size-50 md-small-size-50"
        >
          <md-field
            :class="{
              'md-invalid': errors.has(
                'SmsAndroidSettings-Form.token_' + index,
              ),
            }"
          >
            <label for="token">{{ $tc("words.token") }}</label>
            <md-input
              :id="'token_' + index"
              :name="'token_' + index"
              v-model="smsAndroidSetting.token"
              v-validate="'required|min:3'"
            />
            <span class="md-error">
              {{ errors.first("SmsAndroidSettings-Form.token_" + index) }}
            </span>
          </md-field>
        </div>

        <div
          class="md-layout-item md-xlarge-size-25 md-large-size-25 md-medium-size-25 md-small-size-25"
          style="display: inline-flex"
        >
          <div @click="saveSmsAndroidSetting(smsAndroidSetting)">
            <md-icon style="margin-top: 1.5rem; color: rgb(109 181 246)">
              save
            </md-icon>
          </div>
          <div @click="removeSmsAndroidSetting(smsAndroidSetting.id)">
            <md-icon style="margin-top: 1.5rem; color: #ff0000">delete</md-icon>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { EventBus } from "@/shared/eventbus"

export default {
  name: "SmsAndroidSetting",
  props: {
    smsAndroidSettings: {
      type: Array,
      default: () => [],
    },
  },
  methods: {
    removeSmsAndroidSetting(smsAndroidSettingId) {
      EventBus.$emit("smsAndroidSettingRemoved", smsAndroidSettingId)
    },
    async saveSmsAndroidSetting(smsAndroidSetting) {
      let validator = await this.$validator.validateAll(
        "SmsAndroidSettings-Form",
      )
      if (!validator) {
        return
      }
      EventBus.$emit("smsAndroidSettingSaved", smsAndroidSetting)
    },
    addAdditionalSmsAndroidSetting() {
      EventBus.$emit("smsAndroidSettingAdded")
    },
  },
}
</script>

<style scoped></style>
