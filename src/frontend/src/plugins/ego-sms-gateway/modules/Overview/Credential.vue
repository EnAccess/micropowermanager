<template>
  <form @submit.prevent="submitCredentialForm" data-vv-scope="Credential-Form">
    <md-card>
      <md-card-header>
        <div class="md-title">EgoSMS Gateway Configuration</div>
        <div class="md-subhead">
          Send SMS through your
          <a href="https://www.egosms.co/" target="_blank" rel="noopener">
            EgoSMS
          </a>
          account.
        </div>
      </md-card-header>
      <md-card-content>
        <md-field
          :class="{ 'md-invalid': errors.has('Credential-Form.username') }"
        >
          <label for="username">{{ $tc("words.username") }}</label>
          <md-input
            id="username"
            name="username"
            v-model="credentialService.credential.username"
            v-validate="'required'"
          />
          <span class="md-error">
            {{ errors.first("Credential-Form.username") }}
          </span>
        </md-field>
        <md-field
          :class="{ 'md-invalid': errors.has('Credential-Form.password') }"
        >
          <label for="password">{{ $tc("words.password") }}</label>
          <md-input
            id="password"
            name="password"
            type="password"
            v-model="credentialService.credential.password"
            v-validate="'required'"
          />
          <span class="md-error">
            {{ errors.first("Credential-Form.password") }}
          </span>
        </md-field>
        <md-field
          :class="{ 'md-invalid': errors.has('Credential-Form.senderId') }"
        >
          <label for="senderId">Sender ID</label>
          <md-input
            id="senderId"
            name="senderId"
            v-model="credentialService.credential.senderId"
            v-validate="'required|max:11'"
          />
          <span class="md-helper-text">
            The sender name registered with EgoSMS, at most 11 characters.
          </span>
          <span class="md-error">
            {{ errors.first("Credential-Form.senderId") }}
          </span>
        </md-field>
      </md-card-content>
      <md-progress-bar md-mode="indeterminate" v-if="loading" />
      <md-card-actions>
        <md-button class="md-raised md-primary" type="submit">
          {{ $tc("words.save") }}
        </md-button>
      </md-card-actions>
    </md-card>
  </form>
</template>

<script>
import { CredentialService } from "../../services/CredentialService.js"

import { notify } from "@/mixins/notify.js"
import { EventBus } from "@/shared/eventbus.js"

export default {
  name: "Credential",
  mixins: [notify],
  data() {
    return {
      credentialService: new CredentialService(),
      loading: false,
    }
  },
  mounted() {
    this.credentialService.getCredential()
  },
  methods: {
    async submitCredentialForm() {
      const isValid = await this.$validator.validateAll("Credential-Form")
      if (!isValid) {
        return
      }
      this.loading = true
      try {
        await this.credentialService.updateCredential()
        this.alertNotify("success", "Credentials updated successfully")
        EventBus.$emit("ego_sms_gateway")
      } catch (e) {
        this.alertNotify("error", e.message)
      }
      this.loading = false
    },
  },
}
</script>

<style lang="scss" scoped></style>
