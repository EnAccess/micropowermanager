<template>
  <div>
    <h2>Mail Settings</h2>

    <div class="md-layout md-gutter md-size-100">
      <div class="md-layout-item md-size-33">
        <md-field :class="{ 'md-invalid': errors.has($tc('words.host')) }">
          <label>{{ $tc("words.host") }}</label>
          <md-input
            :name="$tc('words.host')"
            v-model="mailSettingsService.mailSettings.mailHost"
            :id="$tc('words.host')"
            v-validate="'required|min:5|url'"
          ></md-input>
          <span class="md-error">
            {{ errors.first($tc("words.host")) }}
          </span>
        </md-field>
      </div>
      <div class="md-layout-item md-size-33">
        <md-field :class="{ 'md-invalid': errors.has($tc('words.port')) }">
          <label>{{ $tc("words.port") }}</label>
          <md-input
            :name="$tc('words.port')"
            v-model="mailSettingsService.mailSettings.mailPort"
            :id="$tc('words.port')"
            v-validate="'required|min:2|numeric'"
          ></md-input>
          <span class="md-error">
            {{ errors.first($tc("words.port")) }}
          </span>
        </md-field>
      </div>
      <div class="md-layout-item md-size-33">
        <md-field
          :class="{
            'md-invalid': errors.has($tc('words.encryption')),
          }"
        >
          <label>{{ $tc("words.encryption") }}</label>
          <md-input
            :name="$tc('words.encryption')"
            v-model="mailSettingsService.mailSettings.mailEncryption"
            :id="$tc('words.encryption')"
            v-validate="'required|min:3'"
          ></md-input>
          <span class="md-error">
            {{ errors.first($tc("words.encryption")) }}
          </span>
        </md-field>
      </div>
    </div>
    <div class="md-layout md-gutter md-size-100">
      <div class="md-layout-item md-size-50">
        <md-field :class="{ 'md-invalid': errors.has($tc('words.email')) }">
          <label>{{ $tc("words.email") }}</label>
          <md-input
            :name="$tc('words.email')"
            v-model="mailSettingsService.mailSettings.mailUserName"
            :id="$tc('words.email')"
            v-validate="'required|min:8|email'"
          ></md-input>
          <span class="md-error">
            {{ errors.first($tc("words.email")) }}
          </span>
        </md-field>
      </div>
      <div class="md-layout-item md-size-50">
        <md-field :class="{ 'md-invalid': errors.has($tc('words.password')) }">
          <label>{{ $tc("words.password") }}</label>
          <md-input
            :name="$tc('words.password')"
            type="password"
            v-model="mailSettingsService.mailSettings.mailPassword"
            :id="$tc('words.password')"
            v-validate="'required|min:3'"
          ></md-input>
          <span class="md-error">
            {{ errors.first($tc("words.password")) }}
          </span>
        </md-field>
      </div>
    </div>
    <div class="md-layout md-alignment-bottom-right">
      <md-button
        class="md-primary md-dense md-raised"
        @click="updateMailSettings"
      >
        Save
      </md-button>
    </div>
    <md-progress-bar v-if="progress" md-mode="indeterminate"></md-progress-bar>
  </div>
</template>

<script>
import { MailSettingsService } from "@/services/MailSettingsService"
import { notify } from "@/mixins/notify"

export default {
  name: "MailSettings",
  mixins: [notify],
  data() {
    return {
      mailSettingsService: new MailSettingsService(),
      progress: false,
    }
  },
  mounted() {
    this.getMailSettings()
  },
  methods: {
    async getMailSettings() {
      try {
        await this.mailSettingsService.list()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async updateMailSettings() {
      this.progress = true
      let validator = await this.$validator.validateAll()
      if (!validator) {
        this.progress = false
        return
      }
      try {
        await this.mailSettingsService.update()
        this.alertNotify("success", "Updated Successfully")
      } catch (e) {
        this.alertNotify("error", e.message)
      }
      this.progress = false
    },
  },
}
</script>

<style scoped></style>
