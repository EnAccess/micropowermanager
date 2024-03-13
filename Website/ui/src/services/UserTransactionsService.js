import Repository from '../repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'
import { Paginator } from '@/classes/paginator'

export class UserTransactionsService {
    constructor(personId) {
        this.repository = Repository.get('userTransactions')
        this.list = []
        this.personId = personId
        this.paginator = new Paginator(
            '/api/people/' + personId + '/transactions',
        )
    }

    updateList(transactionList) {
        this.list = transactionList.map((transaction) => {
            return this.fromJson(transaction)
        })
        return this.list
    }

    fromJson(transactionData) {
        return {
            id: transactionData.transaction_id,
            paymentType: transactionData.payment_type,
            sender: transactionData.sender,
            amount: transactionData.amount,
            type: transactionData.paid_for_type,
            paymentService: transactionData.payment_service,
            createdAt: transactionData.created_at,
        }
    }

    async getTransactions(userId, page) {
        try {
            let response = await this.repository.list(userId, page)
            if (response.status === 200) {
                return response
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }
}
