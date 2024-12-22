import { ErrorHandler } from "@/Helpers/ErrorHandler"
import MapSettingsRepository from "@/repositories/MapSettingsRepository"

export class MapSettingsService {
  constructor() {
    this.repository = MapSettingsRepository
    this.mapSettings = {
      zoom: null,
      latitude: null,
      longitude: null,
      provider: null,
    }
  }

  async list() {
    try {
      const { status, data, error } = await this.repository.list()
      return status === 200
        ? this.fromJson(data.data[0])
        : new ErrorHandler(error, "http", status)
    } catch (error) {
      return new ErrorHandler(error.response.data.message, "http")
    }
  }

  async update() {
    try {
      const mapSettingsPm = {
        id: this.mapSettings.id,
        zoom: this.mapSettings.zoom,
        latitude: this.mapSettings.latitude,
        longitude: this.mapSettings.longitude,
        provider: this.mapSettings.provider,
      }
      let response = await this.repository.update(
        mapSettingsPm.id,
        mapSettingsPm,
      )
      if (response.status === 200) {
        this.fromJson(response.data.data[0])
        return this.mapSettings
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (error) {
      return new ErrorHandler(error.response.data.message, "http")
    }
  }

  fromJson(mapSettings) {
    this.mapSettings = {
      id: mapSettings.id,
      zoom: mapSettings.zoom,
      latitude: mapSettings.latitude,
      longitude: mapSettings.longitude,
      provider: mapSettings.provider,
    }
    return this.mapSettings
  }
}
