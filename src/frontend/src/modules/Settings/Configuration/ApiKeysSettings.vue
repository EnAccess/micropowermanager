<template>
  <div>
    <div class="md-layout md-gutter">
      <div class="md-layout-item md-size-70 md-small-size-100">
        <md-table v-if="apiKeysService.list && apiKeysService.list.length">
          <md-table-row>
            <md-table-head>Name</md-table-head>
            <md-table-head>Created</md-table-head>
            <md-table-head>Last used</md-table-head>
            <md-table-head>Active</md-table-head>
            <md-table-head></md-table-head>
          </md-table-row>
          <md-table-row v-for="k in apiKeysService.list" :key="k.id">
            <md-table-cell>{{ k.name || "-" }}</md-table-cell>
            <md-table-cell>{{ format(k.created_at) }}</md-table-cell>
            <md-table-cell>
              {{ k.last_used_at ? format(k.last_used_at) : "-" }}
            </md-table-cell>
            <md-table-cell>
              <md-icon v-if="k.active">check</md-icon>
              <md-icon v-else>close</md-icon>
            </md-table-cell>
            <md-table-cell>
              <md-button class="md-accent md-dense" @click="revoke(k.id)">
                Revoke
              </md-button>
            </md-table-cell>
          </md-table-row>
        </md-table>
        <div v-else class="md-body-1">No API keys yet.</div>
      </div>
      <div class="md-layout-item md-size-30 md-small-size-100">
        <md-field>
          <label>Key name (optional)</label>
          <md-input v-model="name" />
        </md-field>
        <md-button
          class="md-primary md-raised"
          :disabled="apiKeysService.generating"
          @click="generate"
        >
          Generate API Key
        </md-button>
        <div v-if="apiKeysService.generatedToken" class="token-box">
          <p>
            <strong>Copy your new API token now.</strong>
            You won't be able to see it again.
          </p>
          <md-field>
            <md-input
              :value="apiKeysService.generatedToken"
              readonly
            ></md-input>
          </md-field>
          <md-button class="md-dense" @click="copy">Copy</md-button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ApiKeysService } from "@/services/ApiKeysService"
import { notify } from "@/mixins"

export default {
  name: "ApiKeysSettings",
  mixins: [notify],
  data() {
    return {
      apiKeysService: new ApiKeysService(),
      name: "",
    }
  },
  async mounted() {
    try {
      await this.apiKeysService.fetch()
    } catch (e) {
      this.alertNotify("error", e.message)
    }
  },
  methods: {
    format(val) {
      return new Date(val).toLocaleString()
    },
    async generate() {
      try {
        await this.apiKeysService.generate(this.name)
        this.alertNotify("success", "API key generated")
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async revoke(id) {
      try {
        await this.apiKeysService.revoke(id)
        this.alertNotify("success", "Key revoked")
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    copy() {
      navigator.clipboard.writeText(this.apiKeysService.generatedToken)
      this.alertNotify("success", "Copied")
    },
  },
}
</script>

<style scoped>
.token-box {
  margin-top: 16px;
}
</style>
