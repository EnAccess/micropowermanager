import { ErrorHandler } from "@/Helpers/ErrorHandler"
import { resources } from "@/resources"
import { Paginator } from "@/Helpers/Paginator"
import { Manufacturers } from "@/services/ManufacturerService"
import { EventBus } from "@/shared/eventbus"

import MeterRepository from "@/repositories/MeterRepository"

export class Meter {
  constructor() {}

  fromJson(jsonData) {
    this.id = jsonData.id
    this.serialNumber = jsonData.serial_number
    this.inUse = jsonData.in_use
    this.lastUpdate = jsonData.updated_at
    this.manufacturerId = jsonData.manufacturer_id
    this.manufacturer = null
    this.type =
      jsonData.meter_type.max_current +
      " A " +
      jsonData.meter_type.phase +
      " P "
    this.online = jsonData.meter_type.online
    this.tariff = jsonData.tariff.name
    return this
  }
}

export class Meters {
  constructor() {
    this.list = []
    this.manufacturerList = []
    this.paginator = new Paginator(resources.meters.list)
    this.manufacturers = new Manufacturers()
  }

  addMeter(meter) {
    this.list.add(meter)
  }

  search(term) {
    this.paginator = new Paginator(resources.meters.search)
    EventBus.$emit("loadPage", this.paginator, { term: term })
  }

  showAll() {
    this.paginator = new Paginator(resources.meters.list)
    EventBus.$emit("loadPage", this.paginator)
  }

  async updateList(data) {
    this.list = []
    if (this.manufacturerList.length === 0) {
      this.manufacturerList = await new Manufacturers().getList()
    }
    for (let m in data) {
      let meter = new Meter()
      meter.fromJson(data[m])
      meter.manufacturer = this.manufacturerList.find(function (_meter) {
        return _meter.id === meter.manufacturerId
      })
      this.list.push(meter)
    }
  }
}

export class MeterService {
  constructor() {
    this.repository = MeterRepository
    this.meters = []
    this.selectedMeter = null
    this.meter = {
      id: null,
      meter_parameter: null,
      serial_number: null,
      max_current: null,
      phase: null,
      tariff: {
        id: null,
        name: null,
        price: null,
      },
      geo: [],
    }
  }

  async getMeterGeos(miniGridId) {
    try {
      let response = await this.repository.geoList(miniGridId)

      if (response.status === 200) {
        this.meters = response.data.data
        return this.meters
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      return new ErrorHandler(e, "http")
    }
  }

  async getMeterDetails(meterId) {
    try {
      let response = await this.repository.get(meterId)

      if (response.status === 200) {
        let data = response.data.data
        let points = [0, 0]
        if (data.meter_parameter.geo != null) {
          points = data.meter_parameter.geo.points.split(",")
        }
        this.meter = {
          id: meterId,
          meter_parameter: data.meter_parameter,
          serial_number: data.serial_number,
          max_current: data.meter_type.max_current,
          phase: data.meter_type.phase,
          tariff: {
            id: data.meter_parameter.tariff.id,
            name: data.meter_parameter.tariff.name,
            price: data.meter_parameter.tariff.price,
          },
          geo: [points[0], points[1]],
        }
        return this.meter
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      return new ErrorHandler(e, "http")
    }
  }

  async updateMeter(meters) {
    try {
      let response = await this.repository.update(meters)
      if (response.status === 200) {
        return response
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      return new ErrorHandler(e, "http")
    }
  }

  getMeters() {
    return this.meters
  }

  addMeter(meter) {
    this.meters.push(meter)
  }
}
