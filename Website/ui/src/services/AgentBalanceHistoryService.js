import { Paginator } from "@/Helpers/Paginator"

import AgentBalanceHistoryRepository from "@/repositories/AgentBalanceHistoryRepository"

export class AgentBalanceHistoryService {
  constructor(agentId) {
    this.repository = AgentBalanceHistoryRepository
    this.list = []
    this.agentId = null
    this.agentBalanceHistory = {
      id: null,
      type: null,
      amount: false,
      createdAt: null,
    }
    this.paginator = new Paginator(resources.agents.balance_histories + agentId)
  }

  fromJson(data) {
    let balanceHistory = {
      id: data.id,
      type: data.trigger_type,
      amount: data.amount,
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
