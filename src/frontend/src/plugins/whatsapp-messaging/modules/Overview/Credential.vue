<template>
  <form
    @submit.prevent="submitCredentialForm"
    data-vv-scope="Credential-Form"
    class="credential-form"
  >
    <md-card>
      <md-card-header>
        <div class="md-title">{{ $t("phrases.whatsappCredentials") }}</div>
      </md-card-header>
      <md-card-content>
        <div class="authorize-div">
          <img v-if="isConnected" src="@/assets/images/authorized.png" />
          <img v-else src="@/assets/images/unauthorized.png" />
          <label>
            {{
              isConnected
                ? $t("phrases.whatsappConnected")
                : $t("phrases.whatsappNotConnected")
            }}
          </label>
        </div>

        <md-field
          :class="{ 'md-invalid': errors.has('Credential-Form.phoneNumberId') }"
        >
          <label for="phoneNumberId">
            {{ $t("phrases.whatsappPhoneNumberId") }}
          </label>
          <md-input
            id="phoneNumberId"
            name="phoneNumberId"
            v-model="credential.phoneNumberId"
            v-validate="'required|numeric'"
          />
          <span class="md-error">
            {{ errors.first("Credential-Form.phoneNumberId") }}
          </span>
        </md-field>

        <md-field
          :class="{
            'md-invalid': errors.has('Credential-Form.businessAccountId'),
          }"
        >
          <label for="businessAccountId">
            {{ $t("phrases.whatsappBusinessAccountId") }}
          </label>
          <md-input
            id="businessAccountId"
            name="businessAccountId"
            v-model="credential.businessAccountId"
            v-validate="'required|numeric'"
          />
          <span class="md-error">
            {{ errors.first("Credential-Form.businessAccountId") }}
          </span>
        </md-field>

        <md-field
          :class="{
            'md-invalid': errors.has('Credential-Form.graphApiVersion'),
          }"
        >
          <label for="graphApiVersion">
            {{ $t("phrases.whatsappGraphApiVersion") }}
          </label>
          <md-input
            id="graphApiVersion"
            name="graphApiVersion"
            placeholder="v21.0"
            v-model="credential.graphApiVersion"
            v-validate="{ required: true, regex: /^v\d+\.\d+$/ }"
          />
          <span class="md-helper-text">
            {{ $t("phrases.whatsappGraphApiVersionHint") }}
          </span>
          <span class="md-error">
            {{ errors.first("Credential-Form.graphApiVersion") }}
          </span>
        </md-field>

        <md-field
          :class="{ 'md-invalid': errors.has('Credential-Form.accessToken') }"
        >
          <label for="accessToken">
            {{ $t("phrases.whatsappAccessToken") }}
          </label>
          <md-input
            id="accessToken"
            name="accessToken"
            type="password"
            autocomplete="new-password"
            v-model="credential.accessToken"
            v-validate="{ required: !credential.isAccessTokenSet }"
          />
          <span v-if="credential.isAccessTokenSet" class="md-helper-text">
            {{
              $t("phrases.whatsappSecretSet", {
                lastFour: credential.accessTokenLastFour,
              })
            }}
          </span>
          <span class="md-error">
            {{ errors.first("Credential-Form.accessToken") }}
          </span>
        </md-field>

        <md-field
          :class="{ 'md-invalid': errors.has('Credential-Form.appSecret') }"
        >
          <label for="appSecret">{{ $t("phrases.whatsappAppSecret") }}</label>
          <md-input
            id="appSecret"
            name="appSecret"
            type="password"
            autocomplete="new-password"
            v-model="credential.appSecret"
            v-validate="{ required: !credential.isAppSecretSet }"
          />
          <span v-if="credential.isAppSecretSet" class="md-helper-text">
            {{
              $t("phrases.whatsappSecretSet", {
                lastFour: credential.appSecretLastFour,
              })
            }}
          </span>
          <span class="md-error">
            {{ errors.first("Credential-Form.appSecret") }}
          </span>
        </md-field>

        <copy-field
          v-if="isConnected"
          :label="$t('phrases.whatsappOptInLink')"
          :value="credential.optInLink"
        />
        <p class="md-caption">
          {{
            isConnected
              ? $t("phrases.whatsappOptInLinkHint")
              : $t("phrases.whatsappOptInLinkPending")
          }}
        </p>

        <div v-if="credentialService.templateErrors.length" class="warning">
          <p>{{ $t("phrases.whatsappTemplateErrors") }}</p>
          <ul>
            <li
              v-for="templateError in credentialService.templateErrors"
              :key="templateError"
            >
              {{ templateError }}
            </li>
          </ul>
        </div>
      </md-card-content>
      <md-progress-bar md-mode="indeterminate" v-if="loading" />
      <md-card-actions>
        <md-button
          class="md-raised md-primary"
          type="submit"
          :disabled="loading"
        >
          {{ $tc("words.save") }}
        </md-button>
      </md-card-actions>
    </md-card>
  </form>
</template>

<script>
import CopyField from "./CopyField.vue"

import { notify } from "@/mixins/notify.js"
import { EventBus } from "@/shared/eventbus.js"

export default {
  name: "Credential",
  components: { CopyField },
  mixins: [notify],
  props: {
    credentialService: { type: Object, required: true },
  },
  data() {
    return {
      loading: false,
    }
  },
  computed: {
    credential() {
      return this.credentialService.credential
    },
    // The backend only builds the opt-in link after Meta has accepted the saved credentials.
    isConnected() {
      return this.credential.optInLink !== null
    },
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
        this.$validator.reset("Credential-Form")
        EventBus.$emit("WhatsappMessaging")
        if (!this.credentialService.templateErrors.length) {
          this.alertNotify(
            "success",
            this.$t("phrases.whatsappCredentialsSaved"),
          )
        }
      } catch (e) {
        this.alertNotify("error", e.message)
      } finally {
        this.loading = false
      }
    },
  },
}
</script>

<style lang="scss" scoped>
.md-card {
  height: 100%;
}

.authorize-div {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.warning {
  margin-top: 1rem;
  padding: 0.5rem 1rem;
  border-left: 4px solid #ff9800;
  background: rgba(255, 152, 0, 0.08);
}
</style>
