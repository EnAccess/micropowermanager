import { ErrorHandler } from "@/Helpers/ErrorHandler"
import MpmPluginRepository from "@/repositories/MpmPluginRepository"

export class MpmPluginService {
  constructor() {
    this.repository = MpmPluginRepository
    this.list = []
  }

  async getMpmPlugins() {
    try {
      let response = await this.repository.list()

      if (response.status === 200 || response.status === 201) {
        this.list = []
        let list = response.data.data
        this.list = list.map((plugin) => {
          return {
            id: plugin.id,
            name: plugin.name,
            description: plugin.description,
            checked: false,
            root_class: plugin.root_class,
            usage_type: plugin.usage_type,
          }
        })
        return this.list
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
