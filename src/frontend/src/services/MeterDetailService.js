import { ErrorHandler } from "@/Helpers/ErrorHandler"
import { convertObjectKeysToSnakeCase } from "@/Helpers/Utils"
import MeterDetailRepository from "@/repositories/MeterDetailRepository"

export class MeterDetailService {
  constructor() {
    this.repository = MeterDetailRepository
    this.meter = {
      id: null,
      serialNumber: null,
      deviceId: null,
      registered: null,
      manufacturer: null,
      tariff: null,
      owner: null,
      connectionType: null,
      connectionGroup: null,
      loaded: false,
      meterType: null,
      totalRevenue: null,
      tokens: [],
      lastPaymentDate: null,
    }
  }

  fromJson(data) {
    this.meter = {
      id: data.id,
      serialNumber: data.serial_number,
      deviceId: data.device.id,
      registered: data.created_at,
      manufacturer: data.manufacturer,
      tariff: data.tariff,
      owner: data.device.person,
      loaded: true,
      meterType: data.meter_type,
      connectionType: data.connection_type,
      connectionGroup: data.connection_group,
      tokens: data.tokens,
      totalRevenue: data.tokens.reduce(
        (acc, curr) => acc + curr.transaction.amount,
        0,
      ),
      lastPaymentDate:
        data.tokens.length > 0
          ? data.tokens.sort(
              (a, b) => new Date(b.created_at) - new Date(a.created_at),
            )[0].created_at
          : null,
    }
  }

  async getDetail(serialNumber) {
    try {
      const { status, data, error } = await this.repository.detail(serialNumber)
      if (status !== 200) return new ErrorHandler(error, "http", status)

      return this.fromJson(data.data)
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async searchPersonForNewOwner(personService, term) {
    try {
      const { status, data } = await personService.searchPerson({
        params: { term: term, paginate: 0 },
      })
      if (status !== 200)
        return new ErrorHandler(data.data.message, "http", status)
      return data.data.map((person) => {
        return {
          id: person.id,
          name: person.name + " " + person.surname,
          toLowerCase: () => person.name.toLowerCase(),
          toString: () => person.name,
        }
      })
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async updateMeterDetails(meterData) {
    const params = convertObjectKeysToSnakeCase(meterData)
    try {
      const { data, status, error } = await this.repository.update(
        meterData.id,
        params,
      )
      if (status !== 200 && status !== 201)
        return new ErrorHandler(error, "http", status)
      return data.data
    } catch (e) {
      return new ErrorHandler(e.response.data.message, "http")
    }
  }
}
