<template>
  <form @submit.prevent="submitCredentialForm" data-vv-scope="Credential-Form">
    <md-card>
      <md-card-header>
        <div class="md-title">Airtel Money Configuration</div>
        <div class="md-subhead">
          Airtel calls MicroPowerManager for every payment. Enter the secret you
          agreed with Airtel and give Airtel the three addresses below.
        </div>
      </md-card-header>
      <md-card-content>
        <md-field
          :class="{ 'md-invalid': errors.has('Credential-Form.jwtSecret') }"
        >
          <label for="jwtSecret">Secret</label>
          <md-input
            id="jwtSecret"
            name="jwtSecret"
            type="password"
            v-model="credentialService.credential.jwtSecret"
            v-validate="'required'"
          />
          <span class="md-helper-text">
            The secret Airtel signs its requests with (HS512).
          </span>
          <span class="md-error">
            {{ errors.first("Credential-Form.jwtSecret") }}
          </span>
        </md-field>
        <md-field
          :class="{ 'md-invalid': errors.has('Credential-Form.phonePrefix') }"
        >
          <label for="phonePrefix">Country calling code</label>
          <md-input
            id="phonePrefix"
            name="phonePrefix"
            placeholder="+255"
            v-model="credentialService.credential.phonePrefix"
            v-validate="{ required: true, regex: /^\+\d{1,4}$/ }"
          />
          <span class="md-helper-text">
            Added when Airtel sends the payer's number without it, for example
            +255 for Tanzania or +256 for Uganda.
          </span>
          <span class="md-error">
            {{ errors.first("Credential-Form.phonePrefix") }}
          </span>
        </md-field>

        <div class="callback-urls">
          <strong>Addresses to give to Airtel</strong>
          <div v-for="step in steps" :key="step.name" class="callback-url">
            <span class="callback-url-label">{{ step.label }}</span>
            <code>{{ callbackUrl(step.name) }}</code>
          </div>
        </div>
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
import { mapGetters } from "vuex"

import { CredentialService } from "../../services/CredentialService.js"

import { notify } from "@/mixins/notify.js"
import { baseUrlExternal } from "@/repositories/Client/AxiosClient.js"
import { EventBus } from "@/shared/eventbus.js"

export default {
  name: "Credential",
  mixins: [notify],
  data() {
    return {
      credentialService: new CredentialService(),
      loading: false,
      steps: [
        { name: "validation", label: "Validation" },
        { name: "process", label: "Process" },
        { name: "enquiry", label: "Enquiry" },
      ],
    }
  },
  computed: {
    ...mapGetters({
      authUser: "auth/getAuthenticateUser",
    }),
  },
  mounted() {
    this.credentialService.getCredential()
  },
  methods: {
    callbackUrl(step) {
      return `${baseUrlExternal}/api/airtel-payment-provider/callback/${this.authUser.companyId}/${step}`
    },
    async submitCredentialForm() {
      const isValid = await this.$validator.validateAll("Credential-Form")
      if (!isValid) {
        return
      }
      this.loading = true
      try {
        await this.credentialService.updateCredential()
        this.alertNotify("success", "Credentials updated successfully")
        EventBus.$emit("airtel_payment_provider")
      } catch (e) {
        this.alertNotify("error", e.message)
      }
      this.loading = false
    },
  },
}
</script>

<style lang="scss" scoped>
.callback-urls {
  margin-top: 2rem;
}

.callback-url {
  margin-top: 0.5rem;
}

.callback-url-label {
  display: inline-block;
  min-width: 6rem;
}
</style>
