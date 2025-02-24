<template>
  <div>
    <md-card>
      <md-card-content>
        <div class="md-layout md-gutter">
          <div
            class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-50"
          >
            <div class="md-layout md-gutter">
              <div
                class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100"
              >
                <p>
                  {{ $tc("phrases.yourAuthTokenForSwifta") }}
                </p>
              </div>
              <div
                class="md-layout-item md-xlarge-size-50 md-large-size-50 md-medium-size-50 md-small-size-50"
              >
                <md-field>
                  <label for="apiToken">
                    {{ $tc("phrases.apiToken") }}
                  </label>
                  <md-input
                    id="apiToken"
                    name="apiToken"
                    v-model="authenticationService.authentication.token"
                    type="password"
                    :disabled="true"
                  />
                </md-field>
              </div>
            </div>
          </div>
        </div>
      </md-card-content>
    </md-card>
  </div>
</template>

<script>
import { AuthenticationService } from "../../services/AuthenticationService"
import { notify } from "@/mixins/notify"

export default {
  name: "Authentication",
  mixins: [notify],
  data() {
    return {
      authenticationService: new AuthenticationService(),
      loading: false,
    }
  },
  mounted() {
    this.getAuthentication()
  },
  methods: {
    async getAuthentication() {
      await this.authenticationService.getAuthentication()
    },
  },
}
</script>

<style scoped></style>
