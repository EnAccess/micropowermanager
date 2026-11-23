<template>
  <div>
    <widget color="primary" title="WhatsApp">
      <md-tabs md-alignment="left">
        <md-tab id="tab-settings" md-label="Settings">
          <credential />
        </md-tab>
        <md-tab id="tab-messages" md-label="Message log">
          <message-log />
        </md-tab>
        <md-tab id="tab-opt-ins" md-label="Opt-ins">
          <opt-ins />
        </md-tab>
      </md-tabs>
    </widget>
  </div>
</template>

<script>
import Credential from "./Credential.vue"
import MessageLog from "./MessageLog.vue"
import OptIns from "./OptIns.vue"

import Widget from "@/shared/Widget.vue"

export default {
  name: "Overview",
  components: { Widget, Credential, MessageLog, OptIns },
}
</script>
