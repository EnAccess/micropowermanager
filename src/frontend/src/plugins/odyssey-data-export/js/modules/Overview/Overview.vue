<template>
  <div>
    <div class="overview-line">
      <div class="md-layout md-gutter">
        <div class="md-layout-item md-small-size-100 md-size-60">
          <md-card>
            <md-card-header>Odyssey Data Export</md-card-header>
            <md-card-content>
              <p>Use the following endpoint on the Odyssey platform:</p>
              <md-field>
                <label>Endpoint URL</label>
                <md-input :value="endpointUrl" readonly></md-input>
              </md-field>
              <md-button class="md-dense" @click="copy(endpointUrl)">
                Copy URL
              </md-button>
              <p class="hint">
                Authentication: Bearer token generated in Settings → API Keys.
              </p>
              <p class="mt-1">Example:</p>
              <pre class="code">
GET {{ endpointUrl }}?FROM=2025-01-01T00:00:00Z&TO=2025-01-01T23:59:59Z</pre
              >
            </md-card-content>
          </md-card>
        </div>
        <div class="md-layout-item md-small-size-100 md-size-40">
          <md-card>
            <md-card-header>Generate API Key</md-card-header>
            <md-card-content>
              <p>Navigate to:</p>
              <md-chip
                class="route-chip"
                @click.native="$router.push({ path: '/settings' })"
              >
                Settings → API Keys
              </md-chip>
              <p class="mt-1">
                Generate a key and copy the token once. Use it as a Bearer token
                when calling the endpoint.
              </p>
            </md-card-content>
          </md-card>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { baseUrl } from "@/repositories/Client/AxiosClient"

export default {
  name: "OdysseyExportOverview",
  data() {
    return {
      endpointUrl: `${baseUrl}/api/odyssey/payments`,
    }
  },
  methods: {
    copy(text) {
      navigator.clipboard.writeText(text)
      this.$swal({
        title: "Copied",
        icon: "success",
        timer: 1200,
        showConfirmButton: false,
      })
    },
  },
}
</script>

<style scoped>
.overview-line {
  margin-top: 1rem;
}
.code {
  background: #2b2b2b;
  color: #eaeaea;
  padding: 8px;
  border-radius: 4px;
}
.mt-1 {
  margin-top: 8px;
}
.route-chip {
  cursor: pointer;
}
</style>
