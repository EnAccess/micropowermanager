import { Paginator } from '../paginator'
import { EventBus } from '@/shared/eventbus'
import { resources } from '@/resources'

export class Transaction {

    constructor () {

    }

    fromJson (transactionData) {
        this.id = transactionData.id
        this.service = transactionData.original_transaction_type
        this.sender = transactionData.sender
        this.amount = transactionData.amount
        this.type = transactionData.type
        this.message = transactionData.message
        this.sentDate = transactionData.created_at
        this.lastUpdate = transactionData.updated_at
        this.status = this.getOriginalData(transactionData).status
    }

    getOriginalData (transactionData) {
        if (transactionData.original_transaction !== undefined) {
            return transactionData.original_transaction
        } else if (transactionData.original_transaction_type === 'airtel_transaction') {
            return transactionData.original_airtel
        } else if (transactionData.original_transaction_type === 'vodacom_transaction') {
            return transactionData.original_vodacom
        }
    }

    /**
   * returns the difference between created_at and updated_at
   */
    getProccessingDuration () {
    }

}

export class Transactions {

    constructor () {
        this.list = []
        this.paginator = new Paginator(resources.transactions.list.all)
    }

    search (term) {
        this.paginator = new Paginator(resources.transactions.search)
        EventBus.$emit('loadPage', this.paginator, {'term': term})
    }

    searchAdvanced (data) {
        this.paginator.url = resources.transactions.searchAdvanced
        this.paginator.method = 'POST'
        this.paginator.setPostData({terms: data})
        EventBus.$emit('loadPage', this.paginator, {'term': data})
    }

    showAll () {
        this.paginator = new Paginator(resources.transactions.list.all)
        EventBus.$emit('loadPage', this.paginator)
    }

    confirmed () {
        this.paginator = new Paginator(resources.transactions.list.confirmed)
        EventBus.$emit('loadPage', this.paginator)
    }

    cancelled () {
        this.paginator = new Paginator(resources.transactions.list.cancelled)
        EventBus.$emit('loadPage', this.paginator)
    }

    updateList (data) {
        this.list = []
        for (let t in data) {
            let transaction = new Transaction()
            transaction.fromJson(data[t])
            this.list.push(transaction)

        }
    }
}
