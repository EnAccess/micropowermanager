import SmsTransactionRepository from "../repositories/SmsTransactionRepository"

export class SmsTransactionService {
  constructor() {
    this.transactions = []
  }

  async getTransactions() {
    const { data } = await SmsTransactionRepository.list()
    this.transactions = data.data
    return this.transactions
  }
}
