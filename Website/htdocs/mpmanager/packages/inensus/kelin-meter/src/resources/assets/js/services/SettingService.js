import Repository from '../repositories/RepositoryFactory'
import { ErrorHandler } from '../Helpers/ErrorHander'
import { SyncSettingService } from './SyncSettingService'

export class SettingService {

    constructor () {
        this.repository = Repository.get('setting')
        this.syncSettingsService = new SyncSettingService()
        this.list = []
        this.setting = {
            id: null,
            actionName: null,
            syncInValueStr: null,
            syncInValueNum: null,
            maxAttempts: null,
        }
    }

    updateList (data) {

        this.list = []
        for (let s in data) {
            this.list.push(data[s].data.attributes)
        }

    }

    async getSettings () {
        try {
            let response = await this.repository.list()
            if (response.status === 200) {
                return this.updateList(response.data.data)
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

    async updateSyncSettings () {
        try {
            await this.syncSettingsService.updateSyncSettings(this.list)
        } catch (e) {
            let errorMessage = e.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }
}