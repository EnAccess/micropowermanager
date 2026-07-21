import { Paginator } from "@/Helpers/Paginator.js"
import AgentBalanceHistoryRepository from "@/repositories/AgentBalanceHistoryRepository.js"
import { resources } from "@/resources.js"

export class AgentBalanceHistoryService {
  constructor(agentId, type) {
    this.repository = AgentBalanceHistoryRepository
    this.list = []
    this.agentId = null
    this.agentBalanceHistory = {
      id: null,
      type: null,
      amount: false,
      createdAt: null,
    }
    const typeQuery = type ? `?type=${type}` : ""
    this.paginator = new Paginator(
      resources.agents.balance_histories + agentId + typeQuery,
    )
  }

  fromJson(data) {
    let balanceHistory = {
      id: data.id,
      type: data.trigger_type,
      amount: data.amount,
      transactionId: data.transaction_id,
      createdAt: data.created_at
        .toString()
        .replace(/T/, " ")
        .replace(/\..+/, ""),
    }
    return balanceHistory
  }

  updateList(data) {
    this.list = data.map(this.fromJson)
    return this.list
  }
}
