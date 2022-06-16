import Repository from '../repositories/RepositoryFactory'
import { EventBus } from '@/shared/eventbus'
import { Paginator } from '@/classes/paginator'
import { ErrorHandler } from '@/Helpers/ErrorHander'

export class AssetService {
    constructor () {
        this.repository = Repository.get('asset')
        this.list = []
        this.asset = {
            id: null,
            name: null,
            updated_at: null,
            edit: false,
            asset_type_name: null,
            price: null
        }
        this.paginator = new Paginator(resources.assets.list)

    }

    fromJson (data) {
        this.id = data.id
        this.name = data.name
        this.updated_at = data.updated_at
        return this
    }

    updateList (data) {
        this.list = data.map(asset => {
            let assetType = {
                id: asset.id,
                name: asset.name,
                updated_at: asset.updated_at.toString().replace(/T/, ' ').replace(/\..+/, ''),
                edit: false,
                price: asset.price
            }
            return assetType
        })
        return this.list
    }

    async createAsset () {
        this.asset.asset_type_name = this.asset.name
        try {
            let response = await this.repository.create(this.asset)
            if (response.status === 200 || response.status === 201) {
                this.asset.id = response.data.data.id
                this.asset.name = response.data.data.name
                this.asset.updated_at = response.data.data.updated_at
                EventBus.$emit('assetTypeAdded', this.asset)
                this.resetAsset()

            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }

    }

    async updateAsset (asset) {
        try {
            const response = await this.repository.update(asset)
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

    async deleteAsset (asset) {
        try {
            let response = await this.repository.delete(asset.id)
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

    async getAssets () {
        try {
            let response = await this.repository.list()
            if (response.status === 200 || response.status === 201) {
                this.list = response.data.data
                return this.list
            } else {
                new ErrorHandler(response.error, 'http', response.status)
            }

        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

    resetAsset () {
        this.asset = {
            id: null,
            name: null,
            updated_at: null,
            edit: false,
            asset_type_name: null,
            price: null
        }
    }

}
