<template>
  <div>
    <form
      @submit.prevent="submitCredentialForm"
      data-vv-scope="Credential-Form"
      class="Credential-Form"
    >
      <md-card>
        <md-card-content>
          <div class="cluster-warning">
            <md-icon class="cluster-warning__icon" style="color: #f9a825">
              warning
            </md-icon>
            <span class="cluster-warning__text">
              When you sync, every Steama site is registered as a MiniGrid under
              your most recently created cluster. Before syncing sites, make
              sure the intended cluster is the latest one you created.
            </span>
          </div>
          <div class="md-layout md-gutter">
            <div
              class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-50"
            >
              <div class="md-layout md-gutter">
                <div
                  class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100"
                >
                  <md-field
                    :class="{
                      'md-invalid': errors.has('Credential-Form.username'),
                    }"
                  >
                    <label for="username">Username</label>
                    <md-input
                      id="username"
                      name="username"
                      v-model="credentialService.credential.username"
                      v-validate="'required|min:3'"
                    />
                    <span class="md-error">
                      {{ errors.first("Credential-Form.username") }}
                    </span>
                  </md-field>
                </div>
                <div
                  class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100"
                >
                  <md-field
                    :class="{
                      'md-invalid': errors.has('Credential-Form.password'),
                    }"
                  >
                    <label for="password">Password</label>
                    <md-input
                      id="password"
                      name="password"
                      type="password"
                      placeholder="Enter password to update credentials"
                      v-model="credentialService.credential.password"
                      v-validate="'min:3'"
                    />
                    <span class="md-error">
                      {{ errors.first("Credential-Form.password") }}
                    </span>
                  </md-field>
                </div>
              </div>
            </div>
            <div
              class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-50"
            >
              <div class="md-layout md-gutter" style="display: grid">
                <div class="md-layout-item md-size-100">
                  <div
                    v-if="credentialService.credential.isAuthenticated"
                    class="authorize-div"
                  >
                    <img src="@/assets/images/authorized.png" />
                    <label style="padding-left: 2rem !important">
                      Authorized
                    </label>
                  </div>

                  <div
                    v-if="!credentialService.credential.isAuthenticated"
                    class="authorize-div"
                  >
                    <img src="@/assets/images/unauthorized.png" />
                    <label style="padding-left: 2rem !important">
                      Unauthorized
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </md-card-content>
        <md-progress-bar md-mode="indeterminate" v-if="loading" />
        <md-card-actions>
          <md-button class="md-raised md-primary" type="submit">Save</md-button>
        </md-card-actions>
      </md-card>
    </form>
  </div>
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
    this.getCredential()
  },
  methods: {
    async getCredential() {
      await this.credentialService.getCredential()
    },
    async submitCredentialForm() {
      let validator = await this.$validator.validateAll("Credential-Form")
      if (!validator) {
        return
      }
      if (!this.credentialService.credential.password) {
        this.alertNotify(
          "warn",
          "Please enter your password to update credentials.",
        )
        return
      }
      try {
        this.loading = true
        const updatedData = await this.credentialService.updateCredential()
        this.alertNotify(updatedData.alert.type, updatedData.alert.message)
        EventBus.$emit("SteamaMeter")
      } catch (e) {
        this.alertNotify("error", "MPM failed to verify your request")
      }
      EventBus.$emit("credentialUpdated")
      this.loading = false
    },
  },
}
</script>

<style lang="scss" scoped>
.md-card {
  height: 100% !important;
}

.Credential-Form {
  height: 100% !important;
}

.cluster-warning {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
  padding: 0.75rem 1rem;
  border: 1px solid #ffe082;
  border-radius: 4px;
  background: #fff8e1;
  color: #8a6d3b;
  line-height: 1.5;
  text-align: left;
}

.cluster-warning__icon {
  flex: 0 0 auto;
}

.cluster-warning__text {
  flex: 1 1 auto;
  text-align: left;
}
</style>
