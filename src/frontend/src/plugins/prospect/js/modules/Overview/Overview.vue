<template>
  <div>
    <widget color="green" title="Prospect Overview">
      <div class="md-layout md-gutter">
        <div
          class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-100"
        >
          <md-card class="setting-card">
            <md-card-header>Configuration Settings</md-card-header>
            <md-card-content>
              <form 
                @submit.prevent="submitForm"
                data-vv-scope="Overview-Form"
                class="Overview-Form"
              >
                <!-- First Row: Endpoint Configuration -->
                <div class="md-layout md-gutter">
                  <div
                    class="md-layout-item md-xlarge-size-50 md-large-size-50 md-medium-size-50 md-small-size-100"
                  >
                    <md-field
                      :class="{
                        'md-invalid': submitted && errors.has('Overview-Form.apiUrl'),
                      }"
                    >
                      <label for="apiUrl">
                        {{ $tc("phrases.apiEndpoint") }}
                      </label>
                      <md-input
                        id="apiUrl"
                        name="apiUrl"
                        v-model="credentialService.credential.apiUrl"
                        v-validate="'required'"
                        placeholder="https://api.example.com/endpoint"
                      />
                      <span class="md-error" v-if="submitted">
                        {{ errors.first("Overview-Form.apiUrl") }}
                      </span>
                    </md-field>
                  </div>
                </div>

                <!-- Synchronization Settings Rows -->
                <div v-for="(setting, i) in settingService.syncList" :key="i">
                  <div class="md-layout md-gutter">
                    <div
                      class="md-layout-item md-xlarge-size-50 md-large-size-50 md-medium-size-50 md-small-size-100"
                    >
                      <md-field>
                        <label>
                          {{ setting.actionName }}
                        </label>
                      </md-field>
                    </div>
                    <div
                      class="md-layout-item md-xlarge-size-50 md-large-size-50 md-medium-size-50 md-small-size-100"
                    >
                      <md-field
                        :class="{
                          'md-invalid': errors.has(
                            'Overview-Form.apiToken_' + setting.id,
                          ),
                        }"
                      >
                        <label for="apiToken">
                          {{ $tc("phrases.apiToken") }}
                        </label>
                        <md-input
                          :id="'apiToken_' + setting.id"
                          :name="'apiToken_' + setting.id"
                          type="password"
                          v-model="setting.apiToken"
                          v-validate="'required|min:3'"
                        />
                        <span class="md-error">
                          {{
                            errors.first(
                              "Overview-Form.apiToken_" + setting.id,
                            )
                          }}
                        </span>
                      </md-field>
                    </div>
                  </div>
                </div>
              </form>
            </md-card-content>
            <md-card-actions>
              <md-button 
                class="md-raised md-primary" 
                type="submit"
                @click="submitForm"
                :disabled="loading"
              >
                {{ $tc("words.save") }}
              </md-button>
            </md-card-actions>
            <md-progress-bar md-mode="indeterminate" v-if="loading" />
          </md-card>
        </div>
      </div>
    </widget>
  </div>
</template>

<script>
import { CredentialService } from "../../services/CredentialService"
import { SettingService } from "../../services/SettingService"
import { EventBus } from "@/shared/eventbus"
import { notify } from "@/mixins/notify"
import Widget from "@/shared/Widget.vue"

export default {
  name: "Overview",
  mixins: [notify],
  components: { Widget },
  data() {
    return {
      credentialService: new CredentialService(),
      settingService: new SettingService(),
      loading: false,
      submitted: false,
    }
  },
  mounted() {
    this.getCredential()
    this.getSettings()
  },
  methods: {
    async getCredential() {
      await this.credentialService.getCredential()
    },
    async getSettings() {
      await this.settingService.getSettings()
    },
    async submitForm() {
      this.submitted = true
      let validator = await this.$validator.validateAll("Overview-Form")
      if (!validator) {
        return
      }
      
      try {
        this.loading = true
        
        // Update credential
        await this.credentialService.updateCredential()
        
        // Update sync settings
        await this.settingService.updateSyncSettings()
        
        this.loading = false
        this.alertNotify("success", "Settings updated successfully")
        EventBus.$emit("Prospect")
      } catch (e) {
        this.loading = false
        this.alertNotify("error", "Failed to update settings")
      }
    },
  },
}
</script>

<style scoped>
.setting-card {
  padding: 2rem !important;
}

.Overview-Form {
  height: 100% !important;
}
</style>
