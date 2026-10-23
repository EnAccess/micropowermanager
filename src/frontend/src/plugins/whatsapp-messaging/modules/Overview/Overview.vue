<template>
  <div class="overview-line">
    <div class="md-layout md-gutter">
      <div
        class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-50"
      >
        <credential :credential-service="credentialService" />
      </div>
      <div
        class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-50"
      >
        <setup-guide :credential="credentialService.credential" />
      </div>
    </div>
  </div>
</template>

<script>
import { CredentialService } from "../../services/CredentialService.js"

import Credential from "./Credential.vue"
import SetupGuide from "./SetupGuide.vue"

import { notify } from "@/mixins/notify.js"

export default {
  name: "Overview",
  components: { Credential, SetupGuide },
  mixins: [notify],
  data() {
    return {
      credentialService: new CredentialService(),
    }
  },
  async mounted() {
    try {
      await this.credentialService.getCredential()
    } catch (e) {
      this.alertNotify("error", e.message)
    }
  },
}
</script>

<style lang="scss" scoped>
.overview-line {
  margin-top: 1rem;
}
</style>
