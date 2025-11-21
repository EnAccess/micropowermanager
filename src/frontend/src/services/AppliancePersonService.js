import { ErrorHandler } from "@/Helpers/ErrorHandler"
import {
  convertObjectKeysToCamelCase,
  convertObjectKeysToSnakeCase,
} from "@/Helpers/Utils"

import AppliancePersonRepository from "@/repositories/AppliancePersonRepository"

export class AppliancePersonService {
  constructor() {
    this.list = []
    this.appliancePerson = {}
    this.repository = AppliancePersonRepository
  }
  fromJson(data) {
    return {
      applianceType: data.appliance,
      applianceTypeId: data.appliance.id,
      creatorId: data.creator_id,
      creatorType: data.creator_type,
      downPayment: data.down_payment,
      createdAt: data.created_at,
      firstPaymentDate: data.first_payment_date,
      personId: data.person_id,
      rateCount: data.rate_count,
      totalCost: data.total_cost,
      totalRemainingAmount: data.totalRemainingAmount,
      totalPayments: data.totalPayments,
      rates: data.rates,
      logs: data.logs,
      device: data.device,
    }
  }
  async getPersonAppliances(id) {
    try {
      const { data, status, error } = await this.repository.list(id)
      if (status !== 200) return new ErrorHandler(error, "http", status)
      this.list = data.data

      return this.list
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
  async show(applianceId) {
    try {
      const { data, status, error } = await this.repository.show(applianceId)
      if (status !== 200) return new ErrorHandler(error, "http", status)

      return this.fromJson(data.data)
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
  async sellAppliance(params) {
    try {
      const appliance = convertObjectKeysToSnakeCase(params)
      const { data, status, error } = await this.repository.create(appliance)
      if (status !== 200 && status !== 201)
        return new ErrorHandler(error, "http", status)
      this.appliancePerson = convertObjectKeysToCamelCase(data.data)

      return this.appliancePerson
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
