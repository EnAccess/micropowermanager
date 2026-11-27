<template>
  <form @submit.prevent="submitCredentialForm" data-vv-scope="Credential-Form">
    <md-card>
      <md-card-header>
        <div class="md-title">Airtel SMS Gateway Configuration</div>
        <div class="md-subhead">
          Send SMS through your Airtel bulk SMS account. Airtel provides the
          connection details (SMPP) together with the account.
        </div>
      </md-card-header>
      <md-card-content>
        <md-field :class="{ 'md-invalid': errors.has('Credential-Form.host') }">
          <label for="host">Host</label>
          <md-input
            id="host"
            name="host"
            type="text"
            v-model="credentialService.credential.host"
            v-validate="'required'"
          />
          <span class="md-helper-text">
            The address of the Airtel SMS server (SMSC), as given by Airtel.
          </span>
          <span class="md-error">
            {{ errors.first("Credential-Form.host") }}
          </span>
        </md-field>
        <md-field :class="{ 'md-invalid': errors.has('Credential-Form.port') }">
          <label for="port">Port</label>
          <md-input
            id="port"
            name="port"
            type="number"
            v-model="credentialService.credential.port"
            v-validate="'required|numeric|between:1,65535'"
          />
          <span class="md-error">
            {{ errors.first("Credential-Form.port") }}
          </span>
        </md-field>
        <md-field
          :class="{ 'md-invalid': errors.has('Credential-Form.systemId') }"
        >
          <label for="systemId">System ID</label>
          <md-input
            id="systemId"
            name="systemId"
            type="text"
            v-model="credentialService.credential.systemId"
            v-validate="'required|max:15'"
          />
          <span class="md-error">
            {{ errors.first("Credential-Form.systemId") }}
          </span>
        </md-field>
        <md-field
          :class="{ 'md-invalid': errors.has('Credential-Form.password') }"
        >
          <label for="password">Password</label>
          <md-input
            id="password"
            name="password"
            type="password"
            v-model="credentialService.credential.password"
            v-validate="'required|max:8'"
          />
          <span class="md-error">
            {{ errors.first("Credential-Form.password") }}
          </span>
        </md-field>
        <md-field
          :class="{ 'md-invalid': errors.has('Credential-Form.sourceAddress') }"
        >
          <label for="sourceAddress">Sender name</label>
          <md-input
            id="sourceAddress"
            name="sourceAddress"
            type="text"
            v-model="credentialService.credential.sourceAddress"
            v-validate="'required|max:11'"
          />
          <span class="md-helper-text">
            The sender name registered with Airtel, at most 11 characters.
          </span>
          <span class="md-error">
            {{ errors.first("Credential-Form.sourceAddress") }}
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
        EventBus.$emit("airtel_sms_gateway")
      } catch (e) {
        this.alertNotify("error", e.message)
      }
      this.loading = false
    },
  },
}
</script>

<style lang="scss" scoped></style>
