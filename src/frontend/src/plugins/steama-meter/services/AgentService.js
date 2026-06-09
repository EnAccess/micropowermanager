import AgentRepository from "../repositories/AgentRepository.js"

import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import { Paginator } from "@/Helpers/Paginator.js"

export class AgentService {
  constructor() {
    this.repository = AgentRepository
    this.list = []
    this.isSync = false
    this.count = 0
    this.pagingUrl = "/api/steama-meters/steama-agent"
    this.routeName = "/steama-meters/steama-agent"
    this.paginator = new Paginator(this.pagingUrl)
    this.agent = {
      id: null,
      name: null,
      surname: null,
      phone: null,
      siteName: null,
      isCreditLimited: null,
      creditBalance: null,
    }
  }

  fromJson(agentData) {
    let person = agentData.mpm_agent?.person
    this.agent = {
      id: agentData.id,
      name: person?.name ?? null,
      surname: person?.surname ?? null,
      phone: person?.addresses?.[0]?.phone ?? null,
      siteName: agentData.site?.mpm_mini_grid?.name ?? null,
      isCreditLimited: agentData.is_credit_limited,
      creditBalance: agentData.credit_balance,
    }
    return this.agent
  }

  updateList(data) {
    this.list = []
    for (let a in data) {
      let agent = this.fromJson(data[a])
      this.list.push(agent)
    }
  }
  async syncAgents() {
    try {
      let response = await this.repository.sync()
      if (response.status === 200) {
        return this.updateList(response.data.data)
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response?.data?.message ?? e.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
  async getAgentsCount() {
    try {
      let response = await this.repository.count()
      if (response.status === 200) {
        this.count = response.data
        return this.count
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response?.data?.message ?? e.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
