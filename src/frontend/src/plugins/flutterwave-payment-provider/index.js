import Component from "./Component.vue"
import Overview from "./modules/Overview/Overview.vue"
import PublicPaymentForm from "./modules/Payment/PublicPaymentForm.vue"
import PublicPaymentResult from "./modules/Payment/PublicPaymentResult.vue"
import Transaction from "./modules/Transaction/Transaction.vue"
import { CredentialService } from "./services/CredentialService.js"
import { PublicPaymentService } from "./services/PublicPaymentService.js"
import { TransactionService } from "./services/TransactionService.js"

export default {
  Overview,
  Transaction,
  Component,
  CredentialService,
  TransactionService,
  PublicPaymentForm,
  PublicPaymentResult,
  PublicPaymentService,
}

export {
  Component,
  CredentialService,
  Overview,
  PublicPaymentForm,
  PublicPaymentResult,
  PublicPaymentService,
  Transaction,
  TransactionService,
}
