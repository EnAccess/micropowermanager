<template>
  <div>
    <div class="overview-line">
      <div class="md-layout md-gutter">
        <div class="md-layout-item md-small-size-100 md-size-60">
          <md-card>
            <md-card-header>
              Ecreee E-Tender Platform Data Export
            </md-card-header>
            <md-card-content>
              <p class="hint">
                Authentication: Bearer token generated in Settings → API Keys.
              </p>
              <p class="mt-1">Example:</p>
              <pre class="code">
GET {{
                  endpointUrl
                }}?startDate=2025-01-01T00:00:00Z&endDate=2025-01-01T23:59:59Z</pre
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
import { baseUrlExternal } from "@/repositories/Client/AxiosClient"

export default {
  name: "EcreeeETenderOverview",
  data() {
    return {
      endpointUrl: `${baseUrlExternal}/api/ecreee-e-tender/ecreee-meter-data`,
    }
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
