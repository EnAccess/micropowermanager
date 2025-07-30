import { ErrorHandler } from "@/Helpers/ErrorHandler"
import PluginRepository from "@/repositories/PluginRepository"

export class PluginService {
  constructor() {
    this.repository = PluginRepository
    this.list = []
  }

  async getPlugins() {
    try {
      this.list = []
      let response = await this.repository.list()
      if (response.status === 200 || response.status === 201) {
        this.list = response.data.data

        return this.list
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
  async updatePlugin(plugin) {
    try {
      let mpmPluginId = plugin.id
      let response = await this.repository.update(mpmPluginId, plugin)
      if (response.status === 200 || response.status === 201) {
        return response.data.data
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
