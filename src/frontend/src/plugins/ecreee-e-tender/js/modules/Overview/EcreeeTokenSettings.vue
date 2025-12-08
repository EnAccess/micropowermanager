<template>
  <md-card>
    <md-card-content>
      <div class="md-layout md-gutter">
        <div
          class="md-layout md-gutter md-size-100 md-small-size-100"
          style="padding: 1rem"
        >
          <div class="md-layout-item md-size-100 md-small-size-100">
            <md-subheader>{{ $tc("phrases.ecreeeTokenSwitch") }}</md-subheader>
            <div
              class="md-layout-item md-size-100 md-small-size-100"
              style="padding: 1rem"
            >
              <span
                class="token-value"
                v-if="ecreeeTokenService.ecreeeToken.token"
              >
                {{ ecreeeTokenService.ecreeeToken.token }}
              </span>

              <md-switch
                v-model="isActive"
                @change="updateToken()"
                class="data-stream-switch"
              >
                <span v-if="!ecreeeTokenService.ecreeeToken.isActive">
                  {{ $tc("words.activate") }}
                </span>
                <span v-else>{{ $tc("words.deactivate") }}</span>
              </md-switch>
            </div>
          </div>
        </div>
      </div>
    </md-card-content>
    <md-progress-bar v-if="progress" md-mode="indeterminate"></md-progress-bar>
  </md-card>
</template>

<script>
import { EcreeeTokenService } from "../../services/EcreeeTokenService"
import { notify } from "@/mixins"
export default {
  name: "EcreeeTokenSettings",
  mixins: [notify],

  data() {
    return {
      ecreeeTokenService: new EcreeeTokenService(),
      isActive: false,
    }
  },
  created() {
    this.getEcreeeToken()
  },

  methods: {
    async getEcreeeToken() {
      await this.ecreeeTokenService.getToken()
      this.isActive = !!this.ecreeeTokenService.ecreeeToken.isActive
    },

    async updateToken() {
      try {
        await this.ecreeeTokenService.activateToken()
        this.alertNotify("success", "Updated Successfully")
      } catch (e) {
        this.alertNotify("error", "Ecreee Token update failed")
      }
    },
  },
}
</script>

<style lang="scss">
.token-value {
  font-size: 16px;
  color: #333;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 4px;
  background-color: #f9f9f9;
}
.data-stream-switch {
  margin-left: 3rem !important;
  float: right;
}
</style>
