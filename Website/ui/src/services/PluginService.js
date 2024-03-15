import Repository from '@/repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'

export class PluginService {
    constructor() {
        this.repository = Repository.get('plugin')
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
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }
    async updatePlugin(plugin) {
        try {
            let mpmPluginId = plugin.id
            let response = await this.repository.update(mpmPluginId, plugin)
            if (response.status === 200 || response.status === 201) {
                return response.data.data
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }
}
