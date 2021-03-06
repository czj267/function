---
--- Generated by EmmyLua(https://github.com/EmmyLua)
--- Created by zack.chen.
--- DateTime: 2018/4/26 20:45
---
local upload = require('resty.upload')
local stringTools = require('stringTool')

local _M = { _VERSION = '0.01' }


-- 获取文件后缀名字
local function getExtension(filename)
    local exts = stringTools.split(filename, "[.]")
    local ext = exts[#exts]
    if not ext then
        return ''
    else
        return ext
    end
end

local function renameFile(oldName,newName)
    local res, err = os.rename(oldName, newName)
    if res == nil then
        ngx.log(ngx.ERR,'文件重命名失败',err)
    end
    return res,err
end

--上传格式为 enctype="multipart/form-data"
--上传文件，在此之前不要使用ngx.req.read_body();
-- 指定要重命名的文件名，可以指定多个键值对，不指定则由系统随机生成
--saveRootPath 上传根目录
--rename { 表单字段=重命名字段, file = 'new_file' }
--chunkSize 每次大小
--timeOut 超时默认10秒，单位秒
--返回 状态码，错误信息/表单值  0:为成功 其他为失败
function _M.Upload(saveRootPath, rename, chunkSize, timeOut)
    if not saveRootPath then
        return 1, "参数saveRootPath不存在"
    end
    local formData = {}
    chunkSize = chunkSize or 1024
    local up, info = upload:new(chunkSize)
    if timeOut then
        timeOut = timeOut * 1000
    else
        timeOut = 10000
    end
    up:set_timeout(timeOut)
    local fileToSave
    local name = ''
    local tmpSaveName = ''
    local ext = ''
    while true do
        local dataType, data = up:read()
        if dataType == 'header' then
            local key = data[1]
            local value = data[2]
            -- 解析表单字段和文件字段
            if key == "Content-Disposition" then
                local kvlist = stringTools.split(value, ';')
                local filename
                --查找表单值和原文件名
                for _, kv in ipairs(kvlist) do
                    local seg = kv
                    if seg:find(' name') then
                        --获取表单名的值
                        local kvfile = stringTools.split(seg, "=")
                        local _name = string.sub(kvfile[2], 2, -2)
                        if _name then
                            name = _name
                        end
                    end
                    --查找filename后面的值
                    if seg:find("filename") then
                        local kvfile = stringTools.split(seg, "=")
                        local _filename = string.sub(kvfile[2], 2, -2)
                        if _filename then
                            --获取原文件名
                            ext = getExtension(_filename)
                            filename = _filename
                        end
                    end
                end
                -- 没有文件名则是普通表单字段
                if not filename then
                    formData[name] = ''
                else
                    --保存
                    tmpSaveName = ngx.time() .. math.random(1, 100) .. '.' .. ext
                    -- 保存文件
                    filePath = saveRootPath .. "/tmp" .. tmpSaveName
                    fileToSave, errMsg = io.open(filePath, "w+")
                    --存储的文件路径
                    if not fileToSave then
                        return 2, "打开文件失败" .. filePath .. errMsg
                    end
                end
            end
        elseif dataType == 'body' then
            if fileToSave then
                fileToSave:write(data)
            else
                formData[name] = data
            end
        elseif dataType == 'part_end' then
            --一个字段完成
            if fileToSave then
                fileToSave:close()
                fileToSave = nil
                local saveName = ''
                --是否指定保存文件名，文件上传先保存为临时文件，然后在根据是否指定保存名字，将临时文件重命名
                if rename ~= nil then
                    local re_name = rename[name]
                    if re_name then
                        saveName = re_name .. '.' .. ext
                        --重命名
                        renameFile(saveRootPath .. "/tmp" ..tmpSaveName, saveRootPath .. "/" ..saveName)
                    else
                        renameFile(saveRootPath .. "/tmp" ..tmpSaveName,saveRootPath .. "/" ..tmpSaveName)
                    end
                end
            else
                renameFile(saveRootPath .. "/tmp" ..tmpSaveName,saveRootPath .. "/" ..tmpSaveName)
            end
        elseif dataType == 'eof' then
            break
        end
    end
    return 0, formData
end

return _M
