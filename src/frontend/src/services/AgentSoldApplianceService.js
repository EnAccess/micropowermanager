import { Paginator } from "@/Helpers/Paginator"

import AgentAssignedApplianceRepository from "@/repositories/AgentAssignedApplianceRepository"
// FIXME:
// - What's the difference between AgentAssignedAppliance and AgentSoldAppliance?
// - Why is the Agent**Sold**ApplianceService using Agent**Assigned**ApplianceRepository
// import AgentSoldApplianceRepository from '@/repositories/AgentSoldApplianceRepository'

export class AgentSoldApplianceService {
  constructor(agentId) {
    this.repository = AgentAssignedApplianceRepository
    this.list = []
    this.soldAppliance = {
      id: null,
      applianceName: null,
      amount: null,
      customerName: null,
      createdAt: null,
    }
    this.paginator = new Paginator(resources.agents.sold_appliances + agentId)
  }

  fromJson(data) {
    let soldAppliance = {
      id: data.id,
      applianceName: data.assigned_appliance.appliance.name,
      amount: data.assigned_appliance.cost,
      customerName: data.person.name + " " + data.person.surname,
      createdAt: data.created_at
        .toString()
        .replace(/T/, " ")
        .replace(/\..+/, ""),
    }
    return soldAppliance
  }

  updateList(data) {
    this.list = data.map(this.fromJson)
    return this.list
  }
}
