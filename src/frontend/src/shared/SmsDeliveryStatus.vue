<template>
  <span class="delivery" v-if="descriptor">
    <md-icon
      class="delivery__icon"
      :class="`delivery__icon--${descriptor.tone}`"
    >
      {{ descriptor.icon }}
    </md-icon>
    <button
      class="delivery__toggle"
      :class="`delivery__toggle--${descriptor.tone}`"
      type="button"
      v-if="hasDetails"
      @click.stop="expanded = !expanded"
    >
      {{ label }}
    </button>
    <span
      class="delivery__label"
      :class="`delivery__label--${descriptor.tone}`"
      v-else-if="label"
    >
      {{ label }}
    </span>
    <span class="delivery__reason" v-if="expanded">
      <span v-if="errorMessage">{{ errorMessage }}</span>
      <span
        class="delivery__attempt"
        v-for="attempt in attempts"
        :key="attempt.id"
      >
        {{ channelName(attempt.gateway_id) }}:
        {{ $t(ATTEMPT_STATUSES[attempt.status] ?? attempt.status) }}
        <template v-if="attempt.template_name">
          ({{ $t("phrases.smsAttemptTemplate") }} {{ attempt.template_name }})
        </template>
        <template v-if="attempt.error_message">
          — {{ attempt.error_message }}
        </template>
      </span>
    </span>
  </span>
</template>

<script>
// Gateway names are product names, so they are not translated.
const CHANNELS = {
  10: "Viber",
  18: "Africa's Talking",
  26: "TextBee",
  33: "WhatsApp",
}

const ATTEMPT_STATUSES = {
  accepted: "phrases.smsAttemptAccepted",
  sent: "phrases.smsAttemptSent",
  delivered: "phrases.smsAttemptDelivered",
  read: "phrases.smsAttemptRead",
  failed: "phrases.smsAttemptFailed",
}

// Messages predating delivery tracking all sit at STORED, so that status is
// treated as "no evidence either way" and renders nothing at all.
const DESCRIPTORS = {
  "-1": {
    icon: "error_outline",
    tone: "warning",
    labelKey: "phrases.smsNotDelivered",
    showLabel: true,
  },
  1: {
    icon: "check",
    tone: "muted",
    labelKey: "phrases.smsSent",
    showLabel: false,
  },
  2: {
    icon: "done_all",
    tone: "muted",
    labelKey: "phrases.smsDelivered",
    showLabel: false,
  },
  3: {
    icon: "schedule",
    tone: "warning",
    labelKey: "phrases.smsWaitingForRetry",
    showLabel: true,
  },
  4: {
    icon: "sync",
    tone: "warning",
    labelKey: "phrases.smsSendingBySms",
    showLabel: true,
  },
}

export function hasDeliveryStatus(status) {
  return Boolean(DESCRIPTORS[status])
}

export default {
  name: "SmsDeliveryStatus",
  props: {
    status: {
      type: Number,
      default: null,
    },
    errorMessage: {
      type: String,
      default: null,
    },
    gatewayId: {
      type: Number,
      default: null,
    },
    attempts: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      ATTEMPT_STATUSES,
      expanded: false,
    }
  },
  computed: {
    descriptor() {
      return DESCRIPTORS[this.status] ?? null
    },
    // The channel says which way the message went, so a sent SMS fallback names the gateway that sent it.
    label() {
      if (!this.descriptor) {
        return ""
      }
      const channel = CHANNELS[this.gatewayId]
      if (!this.descriptor.showLabel) {
        return channel ?? ""
      }
      const label = this.$tc(this.descriptor.labelKey)
      return channel ? `${label} (${channel})` : label
    },
    hasDetails() {
      return Boolean(this.errorMessage) || this.attempts.length > 0
    },
  },
  methods: {
    channelName(gatewayId) {
      return CHANNELS[gatewayId] ?? this.$t("phrases.smsAttemptUnknownChannel")
    },
  },
}
</script>

<style scoped lang="scss">
.delivery {
  display: inline-flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0 0.4em;
  margin-left: 0.3em;
  font-size: 1em;
  vertical-align: middle;
}

.delivery--flush {
  margin-left: 0;
}

.delivery__icon {
  font-size: 1em;
  width: 1em;
  min-width: 1em;
  height: 1em;
  // Vue Material centres icons with auto margins, which swallows the flex gap.
  margin: 0;
}

.delivery__icon--muted {
  color: rgba(0, 0, 0, 0.38) !important;
}

.delivery__icon--warning {
  color: #b26a00 !important;
}

.delivery__label,
.delivery__toggle {
  line-height: 1.4;
}

.delivery__label--muted,
.delivery__toggle--muted {
  color: rgba(0, 0, 0, 0.54);
}

.delivery__label--warning,
.delivery__toggle--warning {
  color: #b26a00;
}

.delivery__toggle {
  background: none;
  border: none;
  padding: 0;
  font: inherit;
  cursor: pointer;
  text-decoration: underline dotted;
  text-underline-offset: 2px;
}

.delivery__reason {
  display: flex;
  flex-direction: column;
  flex-basis: 100%;
  // The SMS thread floats this line to the right of the bubble, where an
  // unbounded gateway error would stretch the float across the column.
  max-width: 320px;
  margin-top: 2px;
  text-align: left;
  color: rgba(0, 0, 0, 0.6);
  white-space: pre-line;
}

.delivery__attempt {
  margin-top: 2px;
}
</style>
