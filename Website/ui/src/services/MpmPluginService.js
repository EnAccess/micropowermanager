import Repository from '@/repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'

export class MpmPluginService {
    constructor () {
        this.repository = Repository.get('mpmPlugin')
        this.list = []
    }

    async getMpmPlugins () {
        try {
            let response = await this.repository.list()

            if (response.status === 200 || response.status === 201) {
                this.list = []
                let list  = response.data.data
                this.list = list.map(plugin => {
                    return  {
                        id:plugin.id,
                        name:plugin.name,
                        description:plugin.description,
                        checked:false,
                        root_class:plugin.root_class,

                    }
                })
                return this.list
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }

    }
}