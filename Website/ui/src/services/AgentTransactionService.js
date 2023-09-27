import Repository from '../repositories/RepositoryFactory'
import { Paginator } from '@/classes/paginator'

export class AgentTransactionService{

    constructor (agentId) {
        this.repository = Repository.get('agentTransactions')
        this.list = []
        this.agentId = null
        this.transaction = {
            id: null,
            amount: null,
            meter: null,
            customer:null,
            createdAt: null
        }
        this.paginator = new Paginator(resources.agents.transactions + agentId)
    }

    fromJson (data) {
        const meterParameter = data.meter ? data.meter.meter_parameter: null
        let transaction = {
            id: data.id,
            amount: data.amount,
            meter: data.message,
            customer: meterParameter ? meterParameter.owner.name + ' ' + meterParameter.owner.surname : '',
            createdAt: data.created_at.toString().replace(/T/, ' ').replace(/\..+/, '')
        }
        return transaction
    }

    updateList (data) {
        this.list = []
        this.list = data.map(transaction => {
            return this.fromJson(transaction)
        })
        return this.list
    }
}
