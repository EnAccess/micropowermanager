export const transactionType = {
  methods: {
    transactionTypeLabel(type) {
      const labels = {
        energy: this.$tc("words.energy"),
        deferred_payment: this.$tc("phrases.deferredPayment"),
        eaas_rate: this.$tc("phrases.eaasRate"),
        down_payment: this.$tc("phrases.downPayment"),
        ad_hoc: this.$tc("phrases.adHoc"),
      }
      return labels[type] || type
    },
  },
}
