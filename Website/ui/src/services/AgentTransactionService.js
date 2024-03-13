import Repository from '../repositories/RepositoryFactory'
import { Paginator } from '@/classes/paginator'

export class AgentTransactionService {
    constructor(agentId) {
        this.repository = Repository.get('agentTransactions')
        this.list = []
        this.agentId = null
        this.transaction = {
            id: null,
            amount: null,
            meter: null,
            customer: null,
            createdAt: null,
        }
        this.paginator = new Paginator(resources.agents.transactions + agentId)
    }

    fromJson(data) {
        return {
            id: data.id,
            amount: data.amount,
            meter: data.message,
            customer:
                data.device.person.name + ' ' + data.device.person.surname,
            createdAt: data.created_at
                .toString()
                .replace(/T/, ' ')
                .replace(/\..+/, ''),
        }
    }

    updateList(data) {
        this.list = []
        this.list = data.map((transaction) => {
            return this.fromJson(transaction)
        })
        return this.list
    }
}
