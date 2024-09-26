export class SteamaTransactionsService {
  constructor() {
    this.list = []
    this.pagingUrl = "/api/steama-meters/steama-transaction/"
    this.routeName = "/steama-meters/steama-transaction/"
    this.steamaTransaction = {
      id: null,
      transactionId: null,
      customerId: null,
      amount: null,
      category: null,
      provider: null,
      timestamp: null,
    }
  }

  fromJson(transactionData) {
    this.steamaTransaction = {
      id: transactionData.id,
      transactionId: transactionData.transaction_id,
      amount: transactionData.amount,
      category: transactionData.category,
      provider: transactionData.provider,
      timestamp: transactionData.timestamp,
    }
    return this.steamaTransaction
  }

  updateList(data) {
    this.list = []
    for (let t in data) {
      let transaction = this.fromJson(data[t])
      this.list.push(transaction)
    }
  }
}
