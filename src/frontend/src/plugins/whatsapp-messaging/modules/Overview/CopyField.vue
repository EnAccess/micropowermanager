<template>
  <md-field>
    <label>{{ label }}</label>
    <md-input :value="value" readonly />
    <md-button class="md-icon-button" :disabled="!value" @click="copy">
      <md-icon>content_copy</md-icon>
    </md-button>
  </md-field>
</template>

<script>
import { notify } from "@/mixins/notify.js"

export default {
  name: "CopyField",
  mixins: [notify],
  props: {
    label: { type: String, required: true },
    value: { type: String, default: null },
  },
  methods: {
    async copy() {
      try {
        await navigator.clipboard.writeText(this.value)
        this.alertNotify("success", this.$t("phrases.copiedToClipboard"))
      } catch (e) {
        this.alertNotify("error", this.$t("phrases.copyToClipboardFailed"))
      }
    },
  },
}
</script>
