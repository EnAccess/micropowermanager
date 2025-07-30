import { ErrorHandler } from "@/Helpers/ErrorHandler"

import AgentAssignedApplianceRepository from "@/repositories/AgentAssignedApplianceRepository"

export class AgentAssignedApplianceService {
  constructor() {
    this.repository = AgentAssignedApplianceRepository
    this.list = []
    this.assignedAppliance = {
      id: null,
      agentId: null,
      personId: null,
      applianceId: null,
      appliance: null,
      cost: null,
    }
  }

  fromJson(data) {
    this.assignedAppliance = {
      id: data.id,
      personId: data.person_id,
      applianceId: data.appliance_id,
      cost: data.cost,
      appliance: data.appliance,
    }

    return this.assignedAppliance
  }

  updateList(data) {
    this.list = data.map((appliance) => {
      return this.fromJson(appliance)
    })
    return this.list
  }

  async getAssignedAppliances(agentId) {
    try {
      let response = await this.repository.list(agentId)
      if (response.status === 200) {
        let list = response.data.data

        this.list = this.updateList(list)
        return this.list
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async assignAppliance(newAppliance, userId, AgentId) {
    try {
      let assignAppliancePM = {
        agent_id: AgentId,
        user_id: userId,
        appliance_id: newAppliance.id,
        cost: newAppliance.cost,
      }
      let response = await this.repository.create(assignAppliancePM)
      if (response.status === 200 || response.status === 201) {
        return response
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
