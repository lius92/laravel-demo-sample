<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Auth;

class SessionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest', [
            //只允许以下页面只作为游客访问
            'only' => ['create']
        ]);
    }

    public function create()
    {
        return view('sessions.create');
    }

    public function store(Request $request)
    {
        $credentials = $this->validate($request, [
            'email' => 'required|email|max:255',
            'password' => 'required'
        ]);

        //attempt 方法会接收一个数组来作为第一个参数，
        //该参数提供的值将用于寻找数据库中的用户数据
        if (Auth::attempt($credentials, $request->has('remember'))) {
            //登陆成功
            session()->flash('success', '欢迎回来');
            //intended 跳转上一次访问的页面
            return redirect()->intended(route('users.show', [Auth::user()]));
        } else {
            session()->flash('danger', '登陆失败,用户不存在或者密码不正确');
            return redirect()->back();
        }
    }

    public function destroy()
    {
        Auth::logout();
        session()->flash('success', '您已经成功退出');
        return redirect()->route('login');
    }

}
