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
      type="button"
      v-if="descriptor.showLabel && errorMessage"
      @click.stop="expanded = !expanded"
    >
      {{ label }}
    </button>
    <span class="delivery__label" v-else-if="descriptor.showLabel">
      {{ label }}
    </span>
    <span class="delivery__reason" v-if="expanded">
      {{ errorMessage }}
    </span>
  </span>
</template>

<script>
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
  },
  data() {
    return {
      expanded: false,
    }
  },
  computed: {
    descriptor() {
      return DESCRIPTORS[this.status] ?? null
    },
    label() {
      return this.descriptor ? this.$tc(this.descriptor.labelKey) : ""
    },
  },
}
</script>

<style scoped lang="scss">
.delivery {
  display: inline-flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0 6px;
  vertical-align: middle;
}

.delivery__icon {
  font-size: 14px;
  width: 14px;
  min-width: 14px;
  height: 14px;
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
  color: #b26a00;
  line-height: 1.4;
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
  flex-basis: 100%;
  // The SMS thread floats this line to the right of the bubble, where an
  // unbounded gateway error would stretch the float across the column.
  max-width: 320px;
  margin-top: 2px;
  text-align: left;
  color: rgba(0, 0, 0, 0.6);
  white-space: pre-line;
}
</style>
