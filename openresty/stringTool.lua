local _M = { _VERSION = '0.01' }

-- 分割字符串
function _M.split(str, split_char)
    local sub_str_tab = {}
    while true do
        local pos = string.find(str, split_char)
        if not pos then
            table.insert(sub_str_tab, str)
            break
        end
        local sub_str = string.sub(str, 1, pos - 1)
        table.insert(sub_str_tab, sub_str)
        str = string.sub(str, pos + 1, string.len(str))
    end
    return sub_str_tab
end



return _M