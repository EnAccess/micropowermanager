import Overview from "./modules/Overview/Overview.vue";
import Transaction from "./modules/Transaction/Transaction.vue";
import Component from "./Component.vue";
import { CredentialService } from "./services/CredentialService";
import { TransactionService } from "./services/TransactionService";

export default {
  Overview,
  Transaction,
  Component,
  CredentialService,
  TransactionService,
};

export { Overview, Transaction, Component, CredentialService, TransactionService };
