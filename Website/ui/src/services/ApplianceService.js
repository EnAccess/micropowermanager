import Repository from '@/repositories/RepositoryFactory'
import { Paginator } from '@/classes/paginator'
import { ErrorHandler } from '@/Helpers/ErrorHander'

export class ApplianceService {
    constructor () {
        this.repository = Repository.get('appliance')
        this.list = []
        this.appliance = {
            id: null,
            name: '',
            edit: false,
            assetTypeId: null,
            assetTypeName: '',
            price: null
        }
        this.paginator = new Paginator(resources.assets.list)
    }

    fromJson (data) {
        this.appliance = {
            id: data.id,
            name: data.name,
            edit: false,
            assetTypeId: data.asset_type_id,
            assetTypeName: data.asset_type.name,
            price: data.price
        }
    }

    async updateAppliance (appliance) {
        try {
            const response = await this.repository.update(appliance)
            if (response.status === 200 || response.status === 201) {
                return response
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }

        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

    async deleteAppliance (appliance) {
        try {
            let response = await this.repository.delete(appliance.id)
            if (response.status === 200 || response.status === 201) {
                return response
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }

        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }

    }

    updateList (data) {
        this.list = []
        this.list = data.map(asset => {
            return {
                id: asset.id,
                name: asset.name,
                price: asset.price,
                assetTypeId: asset.asset_type.id,
                assetTypeName: asset.asset_type.name,
                updatedAt: asset.updated_at.toString().replace(/T/, ' ').replace(/\..+/, ''),
                edit: false,

            }

        })
        return this.list
    }

    async createAppliance () {
        try {
            const appliancePM = {
                name: this.appliance.name,
                asset_type_id: this.appliance.assetTypeId,
                price: this.appliance.price
            }
            let response = await this.repository.create(appliancePM)
            if (response.status === 200 || response.status === 201) {

                this.resetAppliance()

                return response.data.data
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

    resetAppliance () {
        this.appliance = {
            id: null,
            name: '',
            edit: false,
            assetTypeId: null,
            assetTypeName: '',
            price: null
        }
    }

    async getAppliances () {
        try {
            this.list = []
            let response = await this.repository.list()
            if (response.status === 200) {
                this.updateList(response.data.data)
            } else {
                new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }
}