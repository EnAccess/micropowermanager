<template>
  <div>
    <form
      @submit.prevent="submitSiteForm"
      data-vv-scope="ThunderCloud-Form"
      class="ThunderCloud-Form"
    >
      <md-card>
        <md-card-content>
          <div class="md-layout md-gutter">
            <div
              class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-50"
            >
              <div class="advanced-toggle" @click="showAdvanced = !showAdvanced">
                <md-icon class="toggle-icon">
                  {{ showAdvanced ? "expand_less" : "expand_more" }}
                </md-icon>
                <span>Advanced Configuration</span>
              </div>

              <div v-if="showAdvanced" class="md-layout md-gutter">
                <div
                  class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100"
                >
                  <md-field
                    :class="{
                      'md-invalid': errors.has('ThunderCloud-Form.thundercloud_url'),
                    }"
                  >
                    <label for="thundercloud_url">ThunderCloud Base URL</label>
                    <md-input
                      id="thundercloud_url"
                      name="thundercloud_url"
                      v-model="siteService.site.thundercloudUrl"
                      v-validate="'required|min:3'"
                    />
                    <span class="md-error">
                      {{ errors.first("ThunderCloud-Form.thundercloud_url") }}
                    </span>
                  </md-field>
                </div>
                <div
                  class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100"
                >
                  <md-field
                    :class="{
                      'md-invalid': errors.has('ThunderCloud-Form.thundercloud_token'),
                    }"
                  >
                    <label for="thundercloud_token">ThunderCloud Token</label>
                    <md-input
                      id="thundercloud_token"
                      name="thundercloud_token"
                      type="password"
                      v-model="siteService.site.thundercloudToken"
                      v-validate="'required|min:3'"
                    />
                    <span class="md-error">
                      {{ errors.first("ThunderCloud-Form.thundercloud_token") }}
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
                    v-if="siteService.site.isAuthenticated"
                    class="authorize-div"
                  >
                    <img src="@/assets/images/authorized.png" />
                    <label style="padding-left: 2rem !important">
                      Authorized
                    </label>
                  </div>

                  <div
                    v-if="!siteService.site.isAuthenticated"
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
        <md-card-actions v-if="showAdvanced">
          <md-button class="md-raised md-primary" type="submit">Save</md-button>
        </md-card-actions>
      </md-card>
    </form>
  </div>
</template>

<script>
import { SiteService } from "../../services/SiteService.js"

import { notify } from "@/mixins/notify.js"
import { EventBus } from "@/shared/eventbus.js"

export default {
  name: "ThunderCloudConnection",
  mixins: [notify],
  data() {
    return {
      siteService: new SiteService(),
      loading: false,
      showAdvanced: false,
    }
  },
  mounted() {
    this.getSite()
  },
  methods: {
    async getSite() {
      await this.siteService.getSite()
    },
    async submitSiteForm() {
      let validator = await this.$validator.validateAll("ThunderCloud-Form")
      if (!validator) {
        return
      }
      try {
        this.loading = true
        await this.siteService.updateSite(this.siteService.site)
        await this.getSite()
        this.alertNotify("success", "ThunderCloud connection updated.")
        EventBus.$emit("SparkMeter")
      } catch (e) {
        this.alertNotify("error", "MPM failed to verify your request")
      }
      this.loading = false
    },
  },
}
</script>

<style lang="scss" scoped>
.md-card {
  height: 100% !important;
}

.ThunderCloud-Form {
  height: 100% !important;
}

.authorize-div {
  max-width: 30%;
  margin: auto;
  margin-top: 1rem;
}

.advanced-toggle {
  display: flex;
  align-items: center;
  cursor: pointer;
  margin-top: 1rem;
  color: rgba(0, 0, 0, 0.54);
}

.toggle-icon {
  margin: 0 0.25rem 0 0 !important;
}
</style>
