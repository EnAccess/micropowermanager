import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import { Paginator } from "@/Helpers/Paginator.js"
import { convertObjectKeysToSnakeCase } from "@/Helpers/Utils.js"
import AgentSoldApplianceRepository from "@/repositories/AgentSoldApplianceRepository.js"

export class AgentSoldApplianceService {
  constructor(agentId) {
    this.repository = AgentSoldApplianceRepository
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
        // the id has to be the AppliancePerson id: it is what /sold-appliance-detail
        // resolves, and the row is clickable through to that page
        id: data.id,
        applianceName: data.appliance?.name ?? "-",
        amount: data.total_cost ?? 0,
        // an energy service sale finances nothing, so its total_cost is 0 by design;
        // the price per day is the figure that actually describes the deal
        isEnergyService: data.payment_type === "energy_service",
        pricePerDay: data.price_per_day,
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

  async sellAppliance(params) {
    try {
      const { data, status, error } = await this.repository.create(
        convertObjectKeysToSnakeCase(params),
      )
      if (status !== 200 && status !== 201) {
        return new ErrorHandler(error, "http", status)
      }
      return data.data
    } catch (e) {
      const responseData = e.response?.data ?? {}
      const firstError = Object.values(responseData.errors ?? {})[0]?.[0]
      const errorMessage = firstError ?? responseData.message
      return new ErrorHandler(errorMessage, "http", e.response?.status)
    }
  }

  async reloadList() {
    const data = await this.paginator.loadPage(1)

    return this.updateList(data.data)
  }
}
