import { Paginator } from "@/Helpers/Paginator.js"
import AgentAssignedApplianceRepository from "@/repositories/AgentAssignedApplianceRepository.js"
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
    try {
      return {
        id: data.id,
        applianceName: data.assigned_appliance?.appliance?.name ?? "-",
        amount: data.assigned_appliance?.cost ?? 0,
        customerName: data.person
          ? `${data.person.name} ${data.person.surname}`
          : "-",
        createdAt: data.created_at
          ? data.created_at.toString().replace(/T/, " ").replace(/\..+/, "")
          : "-",
      }
    } catch (err) {
      console.error("Failed to parse sold appliance:", data, err)
      return null
    }
  }

  updateList(data) {
    this.list = data
      .map((item) => this.fromJson(item))
      .filter((item) => item !== null)

    return this.list
  }
}
