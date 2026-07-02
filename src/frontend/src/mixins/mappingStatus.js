const STATUS_LABELS = {
  mapped: "phrases.mappingMapped",
  not_mapped: "phrases.mappingNotMapped",
  unsupported: "phrases.mappingUnsupported",
  unknown: "phrases.mappingUnknown",
}

export const mappingStatus = {
  methods: {
    mappingStatusLabel(status) {
      return this.$tc(STATUS_LABELS[status] || STATUS_LABELS.unknown)
    },
  },
}
