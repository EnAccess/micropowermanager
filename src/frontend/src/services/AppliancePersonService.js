import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import {
  convertObjectKeysToCamelCase,
  convertObjectKeysToSnakeCase,
} from "@/Helpers/Utils.js"
import AppliancePersonRepository from "@/repositories/AppliancePersonRepository.js"

export class AppliancePersonService {
  constructor() {
    this.list = []
    this.appliancePerson = {}
    this.repository = AppliancePersonRepository
  }
  fromJson(data) {
    return {
      id: data.id,
      applianceType: data.appliance,
      applianceTypeId: data.appliance.id,
      creatorId: data.creator_id,
      creatorType: data.creator_type,
      downPayment: data.down_payment,
      createdAt: data.created_at,
      deletedAt: data.deleted_at,
      firstPaymentDate: data.first_payment_date,
      personId: data.person_id,
      rateCount: data.rate_count,
      totalCost: data.total_cost,
      totalRemainingAmount: data.totalRemainingAmount,
      totalPayments: data.totalPayments,
      rates: data.rates,
      logs: data.logs,
      device: data.device,
      paymentType: data.payment_type,
      minimumPayableAmount: data.minimum_payable_amount,
      pricePerDay: data.price_per_day,
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
  async updateTotalCost(
    appliancePersonId,
    newTotalCost,
    adminId,
    rateCount = null,
    rateType = null,
  ) {
    try {
      const terms = {
        new_total_cost: newTotalCost,
        admin_id: adminId,
      }
      if (rateCount !== null) terms.rate_count = rateCount
      if (rateType !== null) terms.rate_type = rateType
      const { data, status, error } = await this.repository.updateTotalCost(
        appliancePersonId,
        terms,
      )
      if (status !== 200 && status !== 201) {
        return new ErrorHandler(error, "http", status)
      }
      return this.fromJson(data.data)
    } catch (e) {
      const responseData = e.response?.data ?? {}
      const firstError = Object.values(responseData.errors ?? {})[0]?.[0]
      const errorMessage = firstError ?? responseData.message
      return new ErrorHandler(errorMessage, "http", e.response?.status)
    }
  }
  async delete(appliancePersonId, adminId) {
    try {
      const { data, status, error } = await this.repository.delete(
        appliancePersonId,
        { admin_id: adminId },
      )
      if (status !== 200 && status !== 201) {
        return new ErrorHandler(error, "http", status)
      }
      return this.fromJson(data.data)
    } catch (e) {
      const errorMessage = e.response?.data?.message
      return new ErrorHandler(errorMessage, "http", e.response?.status)
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
