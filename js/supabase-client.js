// supabase-client.js - без конфликтов, с сохранением сессии

(function() {
  const SUPABASE_URL = 'https://dtuipgbsupodwvxlwmse.supabase.co';
  const SUPABASE_ANON_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImR0dWlwZ2JzdXBvZHd2eGx3bXNlIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzQzMzUxNzIsImV4cCI6MjA4OTkxMTE3Mn0.ABaA7-D4jU_64bFGQQZc3J4-0TUH5Lz8PjhqobK8ZvI';
  
  let _supabase = null;
  let _initialized = false;
  
  function initSupabase() {
    if (_initialized) return;
    if (typeof window.supabase !== 'undefined' && window.supabase.createClient) {
      _supabase = window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);
      _initialized = true;
      console.log('✅ Supabase инициализирован');
    }
  }
  
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSupabase);
  } else {
    initSupabase();
  }
  
  setTimeout(() => {
    if (!_initialized && typeof window.supabase !== 'undefined') {
      initSupabase();
    }
  }, 500);
  
  // ========== ГЛОБАЛЬНЫЕ ФУНКЦИИ ==========
  
  window.supabaseSignUp = async function(email, password, metadata) {
    if (!_supabase) throw new Error('Supabase не инициализирован');
    const { data, error } = await _supabase.auth.signUp({
      email: email,
      password: password,
      options: { data: metadata }
    });
    if (error) throw error;
    return data;
  };
  
  window.supabaseSignIn = async function(email, password) {
    if (!_supabase) throw new Error('Supabase не инициализирован');
    const { data, error } = await _supabase.auth.signInWithPassword({
      email: email,
      password: password
    });
    if (error) throw error;
    return data;
  };
  
  window.supabaseCheckAuth = async function() {
    if (!_supabase) return null;
    const { data: { session } } = await _supabase.auth.getSession();
    return session;
  };
  
  window.supabaseGetCurrentUser = async function() {
    if (!_supabase) return null;
    const { data: { user } } = await _supabase.auth.getUser();
    return user;
  };
  
  window.supabaseLogout = async function() {
    if (!_supabase) return;
    await _supabase.auth.signOut();
    window.location.href = '/';
  };
  
  window.supabaseGetUserProfile = async function(userId) {
    if (!_supabase) return null;
    const { data, error } = await _supabase
      .from('profiles')
      .select('*')
      .eq('id', userId)
      .single();
    if (error) return null;
    return data;
  };
  
  window.supabaseUpdateUserProfile = async function(userId, updates) {
    if (!_supabase) throw new Error('Supabase не инициализирован');
    const { data, error } = await _supabase
      .from('profiles')
      .update(updates)
      .eq('id', userId)
      .select()
      .single();
    if (error) throw error;
    return data;
  };
  
  window.supabaseUpsertUserProfile = async function(userId, profileData) {
    if (!_supabase) throw new Error('Supabase не инициализирован');
    const { error } = await _supabase
      .from('profiles')
      .upsert({
        id: userId,
        ...profileData,
        updated_at: new Date().toISOString()
      });
    if (error) throw error;
  };
  
  window.supabaseGetUserContracts = async function(userId) {
    if (!_supabase) return [];
    const { data, error } = await _supabase
      .from('contracts')
      .select('*')
      .eq('user_id', userId)
      .order('contract_date', { ascending: false });
    if (error) return [];
    return data;
  };
  
  window.supabaseGetUserPayments = async function(userId) {
    if (!_supabase) return [];
    const { data, error } = await _supabase
      .from('payments')
      .select('*, contracts(contract_number, pdf_url)')
      .eq('user_id', userId)
      .order('payment_date', { ascending: false });
    if (error) return [];
    return data;
  };
  
  // ========== ФУНКЦИИ ДЛЯ ЗАКАЗОВ И ОПЛАТЫ ==========
  
  window.createOrder = async function(contractId, amount, services, address) {
    if (!_supabase) throw new Error('Supabase не инициализирован');
    const user = await window.supabaseGetCurrentUser();
    if (!user) throw new Error('Пользователь не авторизован');
    
    let contract = null;
    if (contractId) {
      const { data, error } = await _supabase
        .from('contracts')
        .select('*')
        .eq('id', contractId)
        .single();
      if (error) throw error;
      contract = data;
    } else {
      const contractNumber = 'ДГ-' + new Date().toISOString().slice(0,10).replace(/-/g,'') + '-' + Math.floor(Math.random() * 1000);
      const { data, error } = await _supabase
        .from('contracts')
        .insert({
          user_id: user.id,
          contract_number: contractNumber,
          object_address: address,
          services: services,
          total_amount: amount,
          status: 'pending_payment'
        })
        .select()
        .single();
      if (error) throw error;
      contract = data;
    }
    
    const { data: order, error: orderError } = await _supabase
      .from('orders')
      .insert({
        contract_id: contract.id,
        user_id: user.id,
        total_amount: amount,
        status: 'pending'
      })
      .select()
      .single();
    
    if (orderError) throw orderError;
    
    return { contract, order };
  };
  
  window.getUserOrders = async function(userId) {
    if (!_supabase) return [];
    const { data, error } = await _supabase
      .from('orders')
      .select('*, contracts(*)')
      .eq('user_id', userId)
      .order('created_at', { ascending: false });
    if (error) return [];
    return data;
  };
  
  window.updateOrderStatus = async function(orderId, status, paymentId = null) {
    if (!_supabase) throw new Error('Supabase не инициализирован');
    
    const updates = { status: status };
    if (paymentId) updates.yookassa_payment_id = paymentId;
    if (status === 'paid') updates.paid_at = new Date().toISOString();
    
    const { error } = await _supabase
      .from('orders')
      .update(updates)
      .eq('id', orderId);
    
    if (error) throw error;
    
    const { data: order } = await _supabase
      .from('orders')
      .select('contract_id')
      .eq('id', orderId)
      .single();
    
    if (order) {
      await _supabase
        .from('contracts')
        .update({ status: status === 'paid' ? 'signed' : 'pending_payment' })
        .eq('id', order.contract_id);
    }
  };
  
  // Обновление договора
  window.updateContract = async function(contractId, updates) {
    if (!_supabase) throw new Error('Supabase не инициализирован');
    const { data, error } = await _supabase
      .from('contracts')
      .update(updates)
      .eq('id', contractId)
      .select();
    if (error) throw error;
    return data;
  };
  
  console.log('✅ Функции Supabase загружены');
})();